<?php
/**
 * @package    Discussions Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;

class DiscussionsModelTopics extends ListModel
{
	/**
	 * Type data
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $_category = array();

	/**
	 * Category parent data
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_parent = null;

	/**
	 * Model context string.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	public $_context = 'com_discussions.topics';

	/**
	 * Category items array
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_items = null;

	/**
	 * Category items array
	 *
	 * @var    JPagination
	 *
	 * @since  1.0.0
	 */
	protected $_pagination = null;

	/**
	 * Name of the filter form to load
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $filterFormName = 'filter_topics';

	/**
	 * Category tags
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $_categoryTags = array();

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @since  1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 't.id',
				'context', 't.context',
				'item_id', 't.item_id',
				'title', 't.title',
				'text', 't.text',
				'images', 't.images',
				'state', 't.state',
				'created', 't.created',
				'created_by', 't.created_by',
				'attribs', 't.attribs',
				'metakey', 't.metakey',
				'metadesc',
				'access', 't.access',
				'hits', 't.hits',
				'region', 't.region',
				'metadata', 't.metadata',
				'tags_search', 't.tags_search',
				'tags_map', 't.tags_map',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app  = Factory::getApplication('site');
		$user = Factory::getUser();

		// Set id state
		$pk = $app->input->getInt('id', 1);
		$this->setState('category.id', $pk);

		// Load the parameters. Merge Global and Menu Item params into new object
		$params     = $app->getParams();
		$menuParams = new Registry;
		$menu       = $app->getMenu()->getActive();
		if ($menu)
		{
			$menuParams->loadString($menu->getParams());
		}
		$mergedParams = clone $menuParams;
		$mergedParams->merge($params);
		$this->setState('params', $mergedParams);

		// Published state
		$asset = 'com_discussions';
		if ($pk)
		{
			$asset .= '.category.' . $pk;
		}
		if ((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset)))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);
		}
		else
		{
			$this->setState('filter.published', array(0, 1));
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$allregions = $this->getUserStateFromRequest($this->context . '.filter.allregions', 'filter_allregions', '');
		$this->setState('filter.allregions', $allregions);

		$author_id = $this->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id', '');
		$this->setState('filter.author_id', $author_id);

		$onlymy = $this->getUserStateFromRequest($this->context . '.filter.onlymy', 'filter_onlymy', '');
		$this->setState('filter.onlymy', $onlymy);


		// List state information.
		$ordering  = empty($ordering) ? 'last_post_created' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
		parent::populateState($ordering, $direction);

		// Set limit & limitstart for query.
		$this->setState('list.limit', $params->get('topics_limit', 10, 'uint'));
		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string $id A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since  1.0.0
	 */
	protected function getStoreId($id = '')
	{
		$id .= ':' . $this->getState('category.id');
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.allregions');
		$id .= ':' . $this->getState('filter.onlymy');
		$id .= ':' . $this->getState('filter.author_id');

		return parent::getStoreId($id);
	}

	/**
	 * Method to get type data for the current type
	 *
	 * @param   integer $pk The id of the type.
	 *
	 * @return  mixed object|false
	 *
	 * @since  1.0.0
	 */
	public function getCategory($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

		if (!isset($this->_category[$pk]))
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('c.*')
					->from('#__discussions_categories AS c')
					->where('c.id = ' . (int) $pk);

				// Filter by published state.
				$published = $this->getState('filter.published');
				if (is_numeric($published))
				{
					$query->where('c.state = ' . (int) $published);
				}
				elseif (is_array($published))
				{
					$query->where('c.state IN (' . implode(',', $published) . ')');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					return JError::raiseError(404, Text::_('COM_DISCUSSIONS_ERROR_CATEGORY_NOT_FOUND'));
				}

				// Root
				$data->root = ($data->id == 1);

				// Links
				$data->link    = Route::_(DiscussionsHelperRoute::getTopicsRoute($data->id));
				$data->addLink = Route::_(DiscussionsHelperRoute::getFormRoute());

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				// If no access, the layout takes some responsibility for display of limited information.
				$data->params->set('access-view', in_array($data->access, Factory::getUser()->getAuthorisedViewLevels()));

				// Convert metadata fields to objects.
				$data->metadata = new Registry($data->metadata);

				$this->_category[$pk] = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					JError::raiseError(404, $e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->_category[$pk] = false;
				}
			}
		}

		return $this->_category[$pk];
	}

	/**
	 * Get the parent of this category
	 *
	 * @param   integer $pk     The id of the type.
	 * @param  integer  $parent The parent_id of the type.
	 *
	 * @return object
	 *
	 * @since  1.0.0
	 */
	public function &getParent($pk = null, $parent = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

		if (!isset($this->_parent[$pk]))
		{
			$db = Factory::getDbo();
			if (empty($parent))
			{
				$query = $db->getQuery(true)
					->select('parent_id')
					->from('#__discussions_categories')
					->where('id = ' . (int) $pk);
				$db->setQuery($query);
				$parent = $db->loadResult();
			}
			try
			{
				if ($parent > 1)
				{
					$query = $db->getQuery(true)
						->select(array('id', 'title', 'alias', 'parent_id'))
						->from('#__discussions_categories')
						->where('id = ' . (int) $parent);

					$db->setQuery($query);
					$item = $db->loadObject();

					if ($item)
					{

						$item->link = Route::_(DiscussionsHelperRoute::getTopicsRoute($item->id));


						$this->_parent[$pk] = $item;
					}
					else
					{
						$this->_parent[$pk] = false;
					}
				}
				elseif ($parent == 1)
				{
					$root            = new stdClass();
					$root->id        = 1;
					$root->alias     = 'root';
					$root->title     = Text::_('COM_DISCUSSIONS_CATEGORY_ROOT');
					$root->parent_id = 0;

					$this->_parent[$pk] = $root;
				}
				else
				{
					$this->_parent[$pk] = false;
				}

			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					JError::raiseError(404, $e->getMessage());
				}
				else
				{
					$this->setError($e);
					$this->_parent[$pk] = false;
				}
			}
		}

		return $this->_parent[$pk];
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since  1.0.0
	 */
	protected function getListQuery()
	{
		$user  = Factory::getUser();
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array('t.*', 'r.name AS region_name'))
			->from($db->quoteName('#__discussions_topics', 't'));

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE t.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE t.region END)');


		// Join over last post.
		$lastPostQuery = $db->getQuery(true)
			->select('sub_post.id')
			->from('#__discussions_posts as sub_post')
			->where('sub_post.topic_id = t.id')
			->where('sub_post.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
			->where('sub_post.state = 1')
			->order('sub_post.created DESC')
			->setLimit(1);

		$query->select(array(
			'last_post.id as last_post_id',
			'(CASE WHEN last_post.id IS NOT NULL THEN last_post.created ELSE t.created END) as last_post_created',
			'(CASE WHEN last_post.id IS NOT NULL THEN last_post.created_by ELSE t.created_by END) as last_post_created_by',
		))
			->join('LEFT', '#__discussions_posts AS last_post ON last_post.id = (' . (string) $lastPostQuery . ')');


		// Filter by access level.
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('t.access IN (' . $groups . ')');
		}

		// Filter by author
		$authorId = $this->getState('filter.author_id');
		$onlymy   = $this->getState('filter.onlymy');
		if (empty($authorId) && !empty($onlymy) && !$user->guest)
		{
			$authorId = $user->id;
		}
		if (is_numeric($authorId))
		{
			$query->where('t.created_by = ' . (int) $authorId);
		}

		// Filter by published state.
		$published = $this->getState('filter.published');
		if (!empty($published))
		{
			if (is_numeric($published))
			{
				$query->where('( t.state = ' . (int) $published .
					' OR ( t.created_by = ' . $user->id . ' AND t.state IN (0,1)))');
			}
			elseif (is_array($published))
			{
				$query->where('t.state IN (' . implode(',', $published) . ')');
			}
		}

		// Filter by category
		$category = $this->getState('filter.category');
		if ($category > 1)
		{
			$categoryTags = $this->getCategoryTags($category);

			$sql = array();
			foreach ($categoryTags as $category => $tags)
			{
				if (!empty($tags))
				{
					$categorySql = array();

					foreach ($tags as $tag)
					{
						$categorySql[] = $db->quoteName('t.tags_map') . ' LIKE ' . $db->quote('%[' . $tag . ']%');
					}
				}
				$sql[] = '(' . implode(' AND ', $categorySql) . ')';
			}

			if (!empty($sql))
			{
				$query->where('(' . implode(' OR ', $sql) . ')');
			}
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			$cols = array('t.title', 'r.name', 't.text', 't.tags_search', 'ua.name');
			$sql  = array();
			foreach ($cols as $col)
			{
				$sql[] = $db->quoteName($col) . ' LIKE '
					. $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
			}
			$query->where('(' . implode(' OR ', $sql) . ')');
		}

		// Group by
		$query->group(array('t.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 'last_post_created');
		$direction = $this->state->get('list.direction', 'desc');
		$query->order($db->escape($ordering) . ' ' . $db->escape($direction));

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		if (!empty($items))
		{
			foreach ($items as &$item)
			{
				$item->link = Route::_(DiscussionsHelperRoute::getTopicRoute($item->id));

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_discussions.topic', $item->id);
			}
		}

		return $items;
	}

	/**
	 * Gets an array of objects from the results of database query.
	 *
	 * @param   string  $query      The query.
	 * @param   integer $limitstart Offset.
	 * @param   integer $limit      The number of records.
	 *
	 * @return  object[]  An array of results.
	 *
	 * @since  1.0.0
	 * @throws  \RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		return $this->getDbo()->loadObjectList('id');
	}


	/**
	 * Method to get an array of categorytags.
	 *
	 * @param int $pk category id
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getCategoryTags($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : $this->getState('filter.category');
		if (!isset($this->_categoryTags[$pk]))
		{
			try
			{
				$tags = array();
				if (!empty($pk))
				{
					$db    = Factory::getDbo();
					$query = $db->getQuery(true)
						->select(array('c.id', 'c.items_tags'))
						->from($db->quoteName('#__discussions_categories', 'c'))
						->join('LEFT', '#__discussions_categories as this ON c.lft > this.lft AND c.rgt < this.rgt')
						->where('(this.id = ' . (int) $pk . ' OR c.id = ' . $pk . ')');
					$db->setQuery($query);
					$categories = $db->loadObjectList();

					foreach ($categories as $category)
					{
						$tags[$category->id] = array_unique(explode(',', $category->items_tags));
					}

				}
				$this->_categoryTags[$pk] = $tags;
			}
			catch (Exception $e)
			{
				$this->setError($e);
				$this->_categoryTags[$pk] = false;
			}
		}

		return $this->_categoryTags[$pk];
	}


	/**
	 * Gets the value of a user state variable and sets it in the session
	 *
	 * This is the same as the method in \JApplication except that this also can optionally
	 * force you back to the first page when a filter has changed
	 *
	 * @param   string  $key       The key of the user state variable.
	 * @param   string  $request   The name of the variable passed in a request.
	 * @param   string  $default   The default value for the variable if not found. Optional.
	 * @param   string  $type      Filter for the variable, for valid values see {@link \JFilterInput::clean()}. Optional.
	 * @param   boolean $resetPage If true, the limitstart in request is set to zero
	 *
	 * @return  mixed  The request user state.
	 *
	 * @since  1.0.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none', $resetPage = true)
	{
		$app       = Factory::getApplication();
		$set_state = $app->input->get($request, null, $type);
		$new_state = parent::getUserStateFromRequest($key, $request, $default, $type, $resetPage);
		if ($new_state == $set_state)
		{
			return $new_state;
		}
		$app->setUserState($key, $set_state);

		return $set_state;
	}
}