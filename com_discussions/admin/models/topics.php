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
use Joomla\CMS\Helper\TagsHelper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

class DiscussionsModelTopics extends ListModel
{
	/**
	 * Category tags
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $_categoryTags = null;

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
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$this->setState('filter.access', $access);

		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.created_by', $created_by);

		$region = $this->getUserStateFromRequest($this->context . '.filter.region', 'filter_region', '');
		$this->setState('filter.region', $region);

		$category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', '');
		$this->setState('filter.category', $category);

		// List state information.
		$ordering  = empty($ordering) ? 't.created' : $ordering;
		$direction = empty($direction) ? 'desc' : $direction;
		parent::populateState($ordering, $direction);
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
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.region');
		$id .= ':' . $this->getState('filter.category');

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
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('t.*')
			->from($db->quoteName('#__discussions_topics', 't'));

		// Join over the author.
		$offline      = (int) ComponentHelper::getParams('com_profiles')->get('offline_time', 5) * 60;
		$offline_time = Factory::getDate()->toUnix() - $offline;
		$query->select(array(
			'author.id as author_id',
			'author.name as author_name',
			'author.avatar as author_avatar',
			'author.status as author_status',
			'(session.time IS NOT NULL) AS author_online',
			'(company.id IS NOT NULL) AS author_job',
			'company.id as author_job_id',
			'company.name as author_job_name',
			'company.logo as author_job_logo',
			'employees.position as  author_position'
		))
			->join('LEFT', '#__profiles AS author ON author.id = t.created_by')
			->join('LEFT', '#__session AS session ON session.userid = author.id AND session.time > ' . $offline_time)
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = author.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

		// Join over the asset groups.
		$query->select('ag.title AS access_level')
			->join('LEFT', '#__viewlevels AS ag ON ag.id = t.access');

		// Join over the regions.
		$query->select(array('r.id as region_id', 'r.name AS region_name'))
			->join('LEFT', '#__regions AS r ON r.id = 
					(CASE t.region WHEN ' . $db->quote('*') . ' THEN 100 ELSE t.region END)');


		// Filter by access level.
		$access = $this->getState('filter.access');
		if (is_numeric($access))
		{
			$query->where('t.access = ' . (int) $access);
		}

		// Filter by regions
		$region = $this->getState('filter.region');
		if (is_numeric($region))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
			$regionModel = JModelLegacy::getInstance('regions', 'NerudasModel');
			$regions     = $regionModel->getRegionsIds($region);
			$regions[]   = $db->quote('*');
			$regions[]   = $regionModel->getRegion($region)->parent;
			$regions     = array_unique($regions);
			$query->where($db->quoteName('t.region') . ' IN (' . implode(',', $regions) . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('t.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(t.state = 0 OR t.state = 1)');
		}

		// Filter by created_by
		$created_by = $this->getState('filter.created_by');
		if (!empty($created_by))
		{
			$query->where('t.created_by = ' . (int) $created_by);
		}

		// Filter by category
		$category = $this->getState('filter.category');
		if ($category > 1 || $category == 'without')
		{
			$categoryTags = $this->getCategoryTags($category);

			$sql = array();
			foreach ($categoryTags as $tags)
			{
				if (!empty($tags))
				{
					$categorySql = array();
					$operator    = ($category != 'without') ? ' LIKE ' : ' NOT LIKE ';
					foreach ($tags as $tag)
					{

						$categorySql[] = $db->quoteName('t.tags_map') . $operator . $db->quote('%[' . $tag . ']%');
					}
				}
				$operator = ($category != 'without') ? ' AND ' : ' OR ';
				$sql[]    = '(' . implode($operator, $categorySql) . ')';
			}

			if (!empty($sql))
			{
				$operator = ($category != 'without') ? ' OR ' : ' AND ';
				$query->where('(' . implode($operator, $sql) . ')');
			}
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('t.id = ' . (int) substr($search, 3));
			}
			else
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
		}

		// Group by
		$query->group(array('t.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 't.created');
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
				$author_avatar       = (!empty($item->author_avatar) && JFile::exists(JPATH_ROOT . '/' . $item->author_avatar)) ?
					$item->author_avatar : 'media/com_profiles/images/no-avatar.jpg';
				$item->author_avatar = Uri::root(true) . '/' . $author_avatar;

				$item->author_job_logo = (!empty($item->author_job_logo) && JFile::exists(JPATH_ROOT . '/' . $item->author_job_logo)) ?
					Uri::root(true) . '/' . $item->author_job_logo : false;

				// Get Tags
				$item->tags = new TagsHelper;
				$item->tags->getItemTags('com_discussions.topic', $item->id);
			}
		}

		return $items;
	}

	/**
	 * Method to get an array of category tags.
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
						->where($db->quoteName('c.alias') . ' <> ' . $db->quote('root'));
					if ($pk != 'without')
					{
						$query->join('LEFT', '#__discussions_categories as this ON c.lft > this.lft AND c.rgt < this.rgt')
							->where('(this.id = ' . (int) $pk . ' OR c.id = ' . $pk . ')');
					}
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

}