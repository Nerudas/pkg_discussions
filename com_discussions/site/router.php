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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;

class DiscussionsRouter extends RouterView
{
	/**
	 * Router constructor
	 *
	 * @param   JApplicationCms $app  The application object
	 * @param   JMenu           $menu The menu object to work with
	 *
	 * @since  1.0.0
	 */
	public function __construct($app = null, $menu = null)
	{
		// Topics route
		$topics = new RouterViewConfiguration('topics');
		$topics->setKey('id')->setNestable();
		$this->registerView($topics);

		// Topic Form  route
		$topicForm = new RouterViewConfiguration('topicform');
		$topicForm->setKey('catid')->setParent($topics, 'catid');
		$this->registerView($topicForm );

		// Topic route
		$topic = new RouterViewConfiguration('topic');
		$topic->setKey('id')->setParent($topics, 'catid');
		$this->registerView($topic);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/**
	 * Method to get the segment(s) for a items
	 *
	 * @param   string $id    ID of the category to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since  1.0.0
	 */
	public function getTopicsSegment($id, $query)
	{
		$path = array();

		while ($id > 1)
		{
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select(array('id', 'alias', 'parent_id'))
				->from('#__discussions_categories')
				->where('id =' . $id);
			$db->setQuery($dbquery);
			$category = $db->loadObject();

			if ($category)
			{
				$path[$category->id] = $category->alias;
			}
			$id = ($category) ? $category->parent_id : 1;
		}
		$path[1] = 'root';

		return $path;
	}


	/**
	 * Method to get the segment(s) for topic view
	 *
	 * @param   string $id    ID of the item to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since  1.0.0
	 */
	public function getTopicSegment($id, $query)
	{
		return (!empty($id)) ? array($id => $id) : false;
	}

	/**
	 * Method to get the segment(s) for form view
	 *
	 * @param   string $id    ID of the form to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since  1.0.0
	 */
	public function getTopicFormSegment($id, $query)
	{
		$catid = (!empty($query['catid'])) ? $query['catid'] : 1;
		$name  = (!empty($query['id'])) ? 'edit' : 'add';

		return array($catid => $name);
	}

	/**
	 * Method to get the id for a topics
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since  1.0.0
	 */
	public function getTopicsId($segment, $query)
	{
		if (isset($query['id']))
		{
			$parent = $query['id'];

			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select(array('alias', 'id'))
				->from('#__discussions_categories')
				->where($db->quoteName('parent_id') . ' =' . $db->quote($parent));
			$db->setQuery($dbquery);
			$categories = $db->loadObjectList();

			foreach ($categories as $category)
			{
				if ($category->alias == $segment)
				{
					return $category->id;
				}
			}
		}

		return false;
	}

	/**
	 * Method to get the id for topic view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since  1.0.0
	 */
	public function getTopicId($segment, $query)
	{
		return (!empty($segment)) ? $segment : false;
	}

	/**
	 * Method to get the id for form view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since  1.0.0
	 */
	public function getTopicFormId($segment, $query)
	{
		if (in_array($segment, array('form', 'add', 'edit')))
		{
			$catid = (!empty($query['catid'])) ? $query['catid'] : 1;

			return (int) $catid;
		}

		return false;
	}
}

function discussionsBuildRoute(&$query)
{
	$app    = Factory::getApplication();
	$router = new DiscussionsRouter($app, $app->getMenu());

	return $router->build($query);
}

function discussionsParseRoute($segments)
{
	$app    = Factory::getApplication();
	$router = new DiscussionsRouter($app, $app->getMenu());

	return $router->parse($segments);
}