<?php
/**
 * @package    Discussions Component
 * @version    1.0.4
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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\TagsHelper;

class DiscussionsModelTopic extends ListModel
{
	/**
	 * topic data
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $_topic = array();

	/**
	 * Add PostForm data
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $_addPostForm = array();

	/**
	 * Post offset
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $_post_offset = array();

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
	public $_context = 'com_discussions.topic';

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
				'id', 'p.id',
				'topic_id', 'p.topic_id',
				'text', 'p.text',
				'state', 'p.state',
				'created', 'p.created',
				'created_by', 'p.created_by',
				'access', 'p.access',
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
		$this->setState('topic.id', $pk);

		// Set Post State
		$post_id = $app->input->getInt('post_id', 0);
		$this->setState('post.id', $post_id);

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
			$asset .= '.topic.' . $pk;
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

		// List state information.
		$ordering  = empty($ordering) ? 'p.created' : $ordering;
		$direction = empty($direction) ? 'asc' : $direction;
		parent::populateState($ordering, $direction);

		// Set limit & limitstart for query.
		$this->setState('list.limit', $params->get('posts_limit', 10, 'uint'));
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
		$id .= ':' . $this->getState('t.id');
		$id .= ':' . serialize($this->getState('filter.published'));

		return parent::getStoreId($id);
	}

	/**
	 * Method to get topic data for the current type
	 *
	 * @param   integer $pk The id of the type.
	 *
	 * @return  mixed object|false
	 *
	 * @since  1.0.0
	 */
	public function getTopic($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('topic.id');

		if (!isset($this->_topic[$pk]))
		{
			$app  = Factory::getApplication();
			$user = Factory::getUser();

			$errorRedirect = Route::_(DiscussionsHelperRoute::getTopicsRoute());
			$errorMsg      = Text::_('COM_DISCUSSIONS_ERROR_TOPIC_NOT_FOUND');

			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true)
					->select('t.*')
					->from('#__discussions_topics AS t')
					->where('t.id = ' . (int) $pk);

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


				// Filter by published state.
				$published = $this->getState('filter.published');
				if (is_numeric($published))
				{
					$query->where('t.state = ' . (int) $published);
				}
				elseif (is_array($published))
				{
					$query->where('t.state IN (' . implode(',', $published) . ')');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{

					$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

					return false;
				}

				// Links
				$data->link     = Route::_(DiscussionsHelperRoute::getTopicRoute($data->id));
				$data->editLink = false;
				if (!$user->guest && empty($data->context) && empty($data->item_id))
				{
					$userId   = $user->id;
					$asset    = 'com_discussions.topic.' . $data->id;
					$editLink = Route::_(DiscussionsHelperRoute::getTopicFormRoute($data->id));
					// Check general edit permission first.
					if ($user->authorise('core.edit', $asset))
					{
						$data->editLink = $editLink;
					}
					// Now check if edit.own is available.
					elseif (!empty($userId) && $user->authorise('core.edit.own', $asset))
					{
						// Check for a valid user and that they are the owner.
						if ($userId == $data->created_by)
						{
							$data->editLink = $editLink;
						}
					}
				}

				// Convert parameter fields to objects.
				$registry     = new Registry($data->attribs);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				// Get Tags
				$mainTags    = ComponentHelper::getParams('com_discussions')->get('tags');
				$data->tags = new TagsHelper;
				$data->tags->getItemTags('com_discussions.topic', $data->id);
				if (!empty($data->tags->itemTags))
				{
					foreach ($data->tags->itemTags as &$tag)
					{
						$tag->main = (in_array($tag->id, $mainTags));
					}
					$data->tags->itemTags = ArrayHelper::sortObjects($data->tags->itemTags, 'main', -1);
				}

				// Convert the images field to an array.
				$registry     = new Registry($data->images);
				$data->images = $registry->toArray();
				$data->image  = (!empty($data->images) && !empty(reset($data->images)['src'])) ?
					reset($data->images)['src'] : false;

				// If no access, the layout takes some responsibility for display of limited information.
				$data->params->set('access-view', in_array($data->access, Factory::getUser()->getAuthorisedViewLevels()));

				// Convert metadata fields to objects.
				$data->metadata = new Registry($data->metadata);

				$data->postsCount = $this->getTotal();

				// Prepare author data
				$author_avatar         = (!empty($data->author_avatar) && JFile::exists(JPATH_ROOT . '/' . $data->author_avatar)) ?
					$data->author_avatar : 'media/com_profiles/images/no-avatar.jpg';
				$data->author_avatar   = Uri::root(true) . '/' . $author_avatar;
				$data->author_link     = Route::_(ProfilesHelperRoute::getProfileRoute($data->author_id));
				$data->author_job_logo = (!empty($data->author_job_logo) && JFile::exists(JPATH_ROOT . '/' . $data->author_job_logo)) ?
					Uri::root(true) . '/' . $data->author_job_logo : false;
				$data->author_job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($data->author_job_id));


				$this->_topic[$pk] = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{

					$app->redirect($url = $errorRedirect, $msg = $errorMsg, $msgType = 'error', $moved = true);

					return false;
				}
				else
				{
					$this->setError($e);
					$this->_topic[$pk] = false;
				}
			}
		}

		return $this->_topic[$pk];
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
			->select('p.*')
			->from($db->quoteName('#__discussions_posts', 'p'))
			->where('p.topic_id = ' . $this->getState('topic.id', 0));

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
			->join('LEFT', '#__profiles AS author ON author.id = p.created_by')
			->join('LEFT', '#__session AS session ON session.userid = author.id AND session.time > ' . $offline_time)
			->join('LEFT', '#__companies_employees AS employees ON employees.user_id = author.id AND ' .
				$db->quoteName('employees.key') . ' = ' . $db->quote(''))
			->join('LEFT', '#__companies AS company ON company.id = employees.company_id AND company.state = 1');

		// Filter by access level.
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('p.access IN (' . $groups . ')');
		}

		// Filter by published state.
		$published = $this->getState('filter.published');
		if (!empty($published))
		{
			if (is_numeric($published))
			{
				$query->where('( p.state = ' . (int) $published .
					' OR (p.created_by > 0 AND p.created_by = ' . $user->id . ' AND p.state IN (0,1)))');
			}
			elseif (is_array($published))
			{
				$query->where('p.state IN (' . implode(',', $published) . ')');
			}
		}

		// Group by
		$query->group(array('p.id'));

		// Add the list ordering clause.
		$ordering  = $this->state->get('list.ordering', 'p.created');
		$direction = $this->state->get('list.direction', 'asc');
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
			$user   = Factory::getUser();
			$active = $this->getState('post.id', Factory::getApplication()->input->get('post_id'));
			foreach ($items as &$item)
			{
				// Prepare author data
				$item->author_name     = (!empty($item->author_name)) ? $item->author_name : Text::_('COM_PROFILES_GUEST');
				$author_avatar         = (!empty($item->author_avatar) && JFile::exists(JPATH_ROOT . '/' . $item->author_avatar)) ?
					$item->author_avatar : 'media/com_profiles/images/no-avatar.jpg';
				$item->author_avatar   = Uri::root(true) . '/' . $author_avatar;
				$item->author_link     = (!empty($item->author_id)) ? Route::_(ProfilesHelperRoute::getProfileRoute($item->author_id)) : '#none';
				$item->author_job_logo = (!empty($item->author_job_logo) && JFile::exists(JPATH_ROOT . '/' . $item->author_job_logo)) ?
					Uri::root(true) . '/' . $item->author_job_logo : false;
				$item->author_job_link = Route::_(CompaniesHelperRoute::getCompanyRoute($item->author_job_id));

				$item->editLink = false;
				if (!$user->guest && empty($item->context))
				{
					$userId = $user->id;
					$asset  = 'com_discussions.post.' . $item->id;

					$editLink = Route::_(DiscussionsHelperRoute::getPostFormRoute($item->id, $item->topic_id));

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

				$item->form = false;
				if ($item->editLink)
				{
					$formModel = $this->getPostFormModel();
					$formModel->setState('post.id', $item->id);
					$item->form = $formModel->getForm();
					if ($item->form)
					{
						$item->form->bind($item);
					}
				}

				$item->active = ($item->id == $active);

				$item->text = nl2br($item->text);
				preg_match_all('/https?\:\/\/[^\" ]+/i', $item->text, $links);
				if (!empty($links[0]))
				{
					foreach ($links[0] as $link)
					{
						$url  = $link;
						$text = mb_strimwidth(str_replace(array('http://', 'https://'), '', $link), 0, 50, '...');

						$item->text = str_replace($link, '<a href="' . $url . '" target="_blank">' . $text . '</a>', $item->text);
					}
				}
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
	 * Method to get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   1.0.0
	 */
	public function getStart()
	{
		$start = parent::getStart();

		return (!empty($start)) ? $start : $this->getTopicStart();
	}

	/**
	 * Method to get post offset
	 *
	 * @return int
	 * @since  1.0.0
	 */
	public function getTopicStart()
	{
		$app        = Factory::getApplication();
		$limitstart = $this->getState('list.start', $app->input->get('limitstart', 0, 'uint'));
		$pk         = $this->getState('post.id', $app->input->getInt('post_id', 0));

		if (!empty($limitstart) || empty($pk))
		{
			return $limitstart;
		}

		if (!isset($this->_post_offset[$pk]))
		{
			try
			{
				$user   = Factory::getUser();
				$params = $this->getState('params');
				$limit  = $this->getState('list.limit', $params->get('posts_limit', 10, 'uint'));

				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('id')
					->from($db->quoteName('#__discussions_posts', 'p'))
					->where('p.topic_id = ' . $this->getState('topic.id', 0));
				// Filter by access level.
				if (!$user->authorise('core.admin'))
				{
					$groups = implode(',', $user->getAuthorisedViewLevels());
					$query->where('p.access IN (' . $groups . ')');
				}

				// Filter by published state.
				$published = $this->getState('filter.published');
				if (!empty($published))
				{
					if (is_numeric($published))
					{
						$query->where('( p.state = ' . (int) $published .
							' OR ( p.created_by = ' . $user->id . ' AND p.state IN (0,1)))');
					}
					elseif (is_array($published))
					{
						$query->where('p.state IN (' . implode(',', $published) . ')');
					}
				}


				// Add the list ordering clause.
				$ordering  = $this->state->get('list.ordering', 'p.created');
				$direction = $this->state->get('list.direction', 'asc');
				$query->order($db->escape($ordering) . ' ' . $db->escape($direction));
				$db->setQuery($query);
				$postsID = $db->loadColumn();

				$postOffset = 0;
				if (in_array($pk, $postsID))
				{
					foreach ($postsID as $id)
					{
						if ($id == $pk)
						{
							break;
						}
						$postOffset++;
					}
				}
				$page   = floor(($postOffset / $limit) + 1);
				$offset = ($page * $limit) - $limit;

				$this->_post_offset[$pk] = $offset;
			}
			catch (Exception $e)
			{
				$this->setError($e);
				$this->_post_offset[$pk] = 0;
			}
		}

		return $this->_post_offset[$pk];
	}

	/**
	 * Method to get a \JPagination object for the data set.
	 *
	 * @return  \JPagination  A \JPagination object for the data set.
	 *
	 * @since   1.0.0
	 */
	public function getPagination()
	{
		$pagination = parent::getPagination();
		$pagination->setAdditionalUrlParam('post_id', 0);

		return $pagination;
	}

	/**
	 * Increment the hit counter for the article.
	 *
	 * @param   integer $pk Optional primary key of the article to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 *
	 * @since  1.0.0
	 */
	public function hit($pk = 0)
	{
		$app      = Factory::getApplication();
		$hitcount = $app->input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('topic.id');

			$table = Table::getInstance('Topics', 'DiscussionsTable');
			$table->load($pk);
			$table->hit($pk);
		}

		return true;
	}


	/**
	 * Method to get PostForm Model
	 *
	 * @return bool|JModelLegacy
	 *
	 * @since 1.0.0
	 */
	protected function getPostFormModel()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_discussions/models', 'DiscussionsModel');

		return BaseDatabaseModel::getInstance('PostForm', 'DiscussionsModel', array('ignore_request' => true));
	}

	/**
	 * Method to get topic add Post Form
	 *
	 * @param   integer $pk The id of the type.
	 *
	 * @return  mixed object|false
	 *
	 * @since  1.0.0
	 */

	public function getAddPostForm($pk = null)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('topic.id');
		if (!isset($this->_addPostForm[$pk]))
		{
			$user             = Factory::getUser();
			$result           = array();
			$result['action'] = '';
			$result['form']   = false;

			if ($user->authorise('core.create', 'com_discussions.post') || $user->authorise('post.create', 'com_discussions.post'))
			{
				$topic_route = (is_numeric($pk)) ? $pk : 0;
				$action      = DiscussionsHelperRoute::getPostFormRoute(0, $topic_route);
				if (empty($topic_route))
				{
					$action .= '&topic_context=' . $pk;
				}

				$result['action'] = Route::_($action);

				$formModel = $this->getPostFormModel();
				$formModel->setState('post.id', $pk . '_0');
				$form = $formModel->getForm();
				if ($form)
				{
					$form->setValue('id', '', 0);
					$form->setValue('topic_id', '', $pk);
					$form->setValue('created_by', '', Factory::getUser()->id);

					$result['form'] = $form;
				}
				$this->_addPostForm[$pk] = $result;
			}
		}

		return $this->_addPostForm[$pk];
	}
}