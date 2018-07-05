<?php
/**
 * @package    Discussions Component
 * @version    1.0.6
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
use Joomla\CMS\Component\ComponentHelper;

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

		// Topic Form route
		$topicForm = new RouterViewConfiguration('topicform');
		$topicForm->setKey('tag_id')->setParent($topics, 'tag_id');
		$this->registerView($topicForm);

		// Topic route
		$topic = new RouterViewConfiguration('topic');
		$topic->setKey('id')->setParent($topics, 'tag_id');
		$this->registerView($topic);

		// Post Form route
		$postForm = new RouterViewConfiguration('postform');
		$postForm->setKey('topic_id')->setParent($topic, 'topic_id');
		$this->registerView($postForm);


		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/**
	 * Method to get the segment(s) for a items
	 *
	 * @param   string $id    ID of the tag to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since  1.0.0
	 */
	public function getTopicsSegment($id, $query)
	{
		$path = array();
		if ($id > 0)
		{
			$db      = Factory::getDbo();
			$dbquery = $db->getQuery(true)
				->select(array('id', 'alias', 'parent_id'))
				->from('#__tags')
				->where('id =' . $id);
			$db->setQuery($dbquery);
			$tag = $db->loadObject();
			if ($tag)
			{
				$path[$tag->id] = $tag->alias;
			}
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
	 * Method to get the segment(s) for form view
	 *
	 * @param   string $id    ID of the form to retrieve the segments for
	 * @param   array  $query The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 *
	 * @since  1.0.0
	 */
	public function getPostFormSegment($id, $query)
	{
		$topic_id = (!empty($query['topic_id'])) ? $query['topic_id'] : 1;
		$name     = (!empty($query['id'])) ? 'post-edit' : 'post-add';

		return array($topic_id => $name);
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
			$tags = ComponentHelper::getParams('com_discussions')->get('tags');
			// Get tags
			if (!empty($tags) && is_array($tags))
			{
				$db      = Factory::getDbo();
				$dbquery = $db->getQuery(true)
					->select('t.id')
					->from($db->quoteName('#__tags', 't'))
					->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'))
					->where('t.id IN (' . implode(',', $tags) . ')')
					->where($db->quoteName('alias') . ' = ' . $db->quote($segment));
				$db->setQuery($dbquery);
				$id = $db->loadResult();
				return (!empty($id)) ? $id : false;
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
	 * Method to get the id for topic form view
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

	/**
	 * Method to get the id for post form view
	 *
	 * @param   string $segment Segment to retrieve the ID for
	 * @param   array  $query   The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 *
	 * @since  1.0.0
	 */
	public function getPostFormId($segment, $query)
	{
		if (in_array($segment, array('post-form', 'post-add', 'post-edit')))
		{
			$topic_id = (!empty($query['id'])) ? $query['id'] : 1;

			return (int) $topic_id;
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