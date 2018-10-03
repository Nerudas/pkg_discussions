<?php
/**
 * @package    Discussions Component
 * @version    1.2.0
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
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

JLoader::register('FieldTypesFilesHelper', JPATH_PLUGINS . '/fieldtypes/files/helper.php');

class DiscussionsModelTopics extends ListModel
{
	/**
	 * This tag
	 *
	 * @var    object
	 * @since  1.0.0
	 */
	protected $_tag = null;

	/**
	 * Authors data
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $_authors = array();

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
		$this->setState('tag.id', $pk);

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
		$id .= ':' . $this->getState('tag.id');
		$id .= ':' . serialize($this->getState('filter.published'));
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.onlymy');
		$id .= ':' . $this->getState('filter.author_id');

		return parent::getStoreId($id);
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
		$query->select(array('r.id as region_id', 'r.name as region_name'))
			->join('LEFT', '#__location_regions AS r ON r.id = t.region');

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
			'(CASE WHEN last_post.id IS NOT NULL THEN last_post.text ELSE t.text END) as last_post_text',
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

		// Filter by tag.
		$tag = (int) $this->getState('tag.id');
		if ($tag > 1)
		{
			$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap')
				. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('t.id')
				. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_discussions.topic'))
				->where($db->quoteName('tagmap.tag_id') . ' = ' . $tag);
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
			JLoader::register('DiscussionsHelperTopic', JPATH_SITE . '/components/com_discussions/helpers/topic.php');
			$topicsAuthors = ArrayHelper::getColumn($items, 'created_by');
			$postsAuthors  = ArrayHelper::getColumn($items, 'last_post_created_by');
			$authors       = $this->getAuthors(array_unique(array_merge($topicsAuthors, $postsAuthors)));
			$user          = Factory::getUser();
			$mainTags      = ComponentHelper::getParams('com_discussions')->get('tags', array());
			$imagesHelper  = new FieldTypesFilesHelper();
			foreach ($items as &$item)
			{
				$item->link           = Route::_(DiscussionsHelperRoute::getTopicRoute($item->id));
				$item->last_post_link = (!empty($item->last_post_id)) ?
					Route::_(DiscussionsHelperRoute::getTopicRoute($item->id) . '&post_id=' . $item->last_post_id) :
					$item->link;
				$item->editLink       = false;
				if (!$user->guest && empty($item->context))
				{
					$userId = $user->id;
					$asset  = 'com_discussions.topic.' . $item->id;

					$editLink = Route::_(DiscussionsHelperRoute::getTopicFormRoute($item->id));

					// Check general edit permission first.
					if ($user->authorise('core.edit', $asset))
					{
						$item->editLink = $editLink;
					}
					// Now check if edit.own is available.
					elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
					{
						// Check for a valid user and that they are the owner.
						if ($userId == $item->created_by)
						{
							$item->editLink = $editLink;
						}
					}
				}

				$item->postsCount = DiscussionsHelperTopic::getPostsTotal($item->id);

				$imageFolder  = 'images/discussions/topics/' . $item->id;
				$registry     = new Registry($item->images);
				$item->images = $registry->toArray();
				$item->images = $imagesHelper->getImages('content', $imageFolder, $item->images,
					array('text' => true, 'for_field' => false));
				$item->image  = (!empty($item->images) && !empty(reset($item->images)->src)) ?
					reset($item->images)->src : false;

				$item->author = (isset($authors[$item->created_by])) ? $authors[$item->created_by] : $authors[0];

				$item->last_post_author = (isset($authors[$item->last_post_created_by])) ?
					$authors[$item->last_post_created_by] : $authors[0];

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_discussions.topic', $item->id);

				if (!empty($item->tags->itemTags))
				{
					foreach ($item->tags->itemTags as &$tag)
					{
						$tag->main = (in_array($tag->id, $mainTags));
					}
					$item->tags->itemTags = ArrayHelper::sortObjects($item->tags->itemTags, 'main', -1);
				}

				// Change shortcodes layout
				$item->text           = str_replace('layout="discussions"', 'layout="discussions_preview"', $item->text);
				$item->last_post_text = str_replace('layout="discussions"', 'layout="discussions_preview"', $item->last_post_text);
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
	 * Get the current tag
	 *
	 * @param null $pk
	 *
	 * @return object|false
	 *
	 * @since 1.0.0
	 */
	public function getTag($pk = null)
	{
		if (!is_object($this->_tag))
		{
			$app    = Factory::getApplication();
			$pk     = (!empty($pk)) ? (int) $pk : (int) $this->getState('tag.id', $app->input->get('id', 1));
			$tag_id = $pk;

			$root            = new stdClass();
			$root->title     = Text::_('JGLOBAL_ROOT');
			$root->id        = 1;
			$root->parent_id = 0;
			$root->link      = Route::_(DiscussionsHelperRoute::getTopicsRoute(1));
			$root->addLink   = Route::_(DiscussionsHelperRoute::getTopicFormRoute());
			$root->metadata  = new Registry();

			if ($tag_id > 1)
			{
				$errorRedirect = Route::_(DiscussionsHelperRoute::getTopicsRoute(1));
				$errorMsg      = Text::_('COM_DISCUSSION_ERROR_TAG_NOT_FOUND');
				try
				{
					$db    = $this->getDbo();
					$query = $db->getQuery(true)
						->select(array('t.id', 't.parent_id', 't.title', 'pt.title as parent_title',
							'mt.metakey', 'mt.metadesc', 'mt.metadata'))
						->from('#__tags AS t')
						->where('t.id = ' . (int) $tag_id)
						->join('LEFT', '#__tags AS pt ON pt.id = t.parent_id')
						->join('LEFT', '#__discussions_tags AS mt ON mt.id = t.id');

					$user = Factory::getUser();
					if (!$user->authorise('core.admin'))
					{
						$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
					}
					if (!$user->authorise('core.manage', 'com_tags'))
					{
						$query->where('t.published =  1');
					}

					$db->setQuery($query);
					$data = $db->loadObject();

					if (empty($data))
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

						return false;
					}

					$data->link    = Route::_(DiscussionsHelperRoute::getTopicsRoute($data->id));
					$data->addLink = Route::_(DiscussionsHelperRoute::getTopicFormRoute());

					$imagesHelper = new FieldTypesFilesHelper();
					$imageFolder  = 'images/discussions/tags/' . $data->id;

					// Convert the metadata field
					$data->metadata = new Registry($data->metadata);
					$data->metadata->set('image', $imagesHelper->getImage('meta', $imageFolder, false, false));
					$this->_tag = $data;


					$this->_tag = $data;
				}
				catch (Exception $e)
				{
					if ($e->getCode() == 404)
					{
						$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);
					}
					else
					{
						$this->setError($e);
						$this->_tag = false;
					}
				}
			}
			else
			{
				$this->_tag = $root;
			}
		}

		return $this->_tag;
	}


	/**
	 * Method to get Authors
	 *
	 * @param array $pks User Ids
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	protected function getAuthors($pks = array())
	{
		$pks = (!is_array($pks)) ? (array) $pks : array_unique($pks);

		if (!isset($this->_authors[0]))
		{
			$author           = new stdClass();
			$author->id       = 0;
			$author->name     = Text::_('COM_PROFILES_GUEST');
			$author->avatar   = Uri::root(true) . '/media/com_profiles/images/no-avatar.jpg';
			$author->status   = '';
			$author->online   = 0;
			$author->job      = false;
			$author->job_id   = '';
			$author->job_name = '';
			$author->job_logo = false;
			$author->position = '';
			$author->link     = false;
			$author->job_link = '';

			$this->_authors[0] = $author;
		}

		$authors    = array();
		$authors[0] = $this->_authors[0];

		if (!empty($pks))
		{
			$getAuthors = array();
			foreach ($pks as $pk)
			{
				if (isset($this->_authors[$pk]))
				{
					$authors[$pk] = $this->_authors[$pk];
				}
				else
				{
					$getAuthors[] = $pk;
				}
			}

			if (!empty($getAuthors))
			{
				try
				{
					$db           = Factory::getDbo();
					$offline      = (int) ComponentHelper::getParams('com_profiles')->get('offline_time', 5) * 60;
					$offline_time = Factory::getDate()->toUnix() - $offline;

					$query = $db->getQuery(true)
						->select(array(
							'p.id as id',
							'p.name as name',
							'p.status as status',
							'(s.time IS NOT NULL) AS online',
							'(c.id IS NOT NULL) AS job',
							'c.id as job_id',
							'c.name as job_name',
							'e.position as  position'
						))
						->from($db->quoteName('#__profiles', 'p'))
						->join('LEFT', '#__session AS s ON s.userid = p.id AND s.time > ' . $offline_time)
						->join('LEFT', '#__companies_employees AS e ON e.user_id = p.id AND ' .
							$db->quoteName('e.key') . ' = ' . $db->quote(''))
						->join('LEFT', '#__companies AS c ON c.id = e.company_id AND c.state = 1')
						->join('LEFT', '#__users AS u ON u.id = p.id')
						->where('u.block = 0')
						->where('p.id IN (' . implode(',', $getAuthors) . ')');
					$db->setQuery($query);
					$objects      = $db->loadObjectList('id');
					$imagesHelper = new FieldTypesFilesHelper();
					foreach ($objects as $object)
					{
						$object->avatar = $imagesHelper->getImage('avatar', 'images/profiles/' . $object->id,
							'media/com_profiles/images/no-avatar.jpg', false);

						$object->link = Route::_(ProfilesHelperRoute::getProfileRoute($object->id));

						$object->job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($object->job_id));

						$authors[$object->id] = $object;

						$this->_authors[$object->id] = $object;
					}

				}
				catch (Exception $e)
				{
					$this->setError($e);
				}
			}
		}

		return $authors;
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

	/**
	 * Get the filter form
	 *
	 * @param   array   $data     data
	 * @param   boolean $loadData load current data
	 *
	 * @return  Form|boolean  The Form object or false on error
	 *
	 * @since 1.0.0
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		if ($form = parent::getFilterForm())
		{
			$params = $this->getState('params');
			if ($params->get('search_placeholder', ''))
			{
				$form->setFieldAttribute('search', 'hint', $params->get('search_placeholder'), 'filter');
			}
			$form->setValue('tag', 'filter', $this->getState('tag.id', 1));
		}

		return $form;
	}


}