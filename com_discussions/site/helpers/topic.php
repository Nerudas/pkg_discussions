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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

class DiscussionsHelperTopic
{
	/**
	 * Posts count
	 *
	 * @var    int
	 *
	 * @since  1.0.0
	 */
	protected static $_postCount = array();

	/**
	 * Integration
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected static $_integration = array();

	/**
	 * Method to get topic posts count
	 *
	 * @param   integer $pk The id of the topic.
	 *
	 * @return  int
	 *
	 * @since  1.0.0
	 */
	public static function getPostsTotal($pk)
	{
		if (!isset(self::$_postCount[$pk]))
		{
			if (!empty($pk))
			{
				$model = self::getTopicModel();
				$model->setState('topic.id', $pk);
				self::$_postCount[$pk] = $model->getTotal();
			}
			else
			{
				self::$_postCount[$pk] = 0;
			}

		}

		return self::$_postCount[$pk];
	}


	/**
	 * Method to get integration object for third path components
	 *
	 * @param array $data Integration data
	 *
	 * @return bool| object
	 *
	 * @since 1.0.0
	 */
	public static function getIntegration($data = array())
	{
		// Load Language
		$language = Factory::getLanguage();
		$language->load('com_discussions', JPATH_SITE, $language->getTag());

		// Load routers
		JLoader::register('DiscussionsHelperRoute', JPATH_SITE . '/components/com_discussions/helpers/route.php');
		JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');
		JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');

		if (empty($data['context']) || empty($data['item_id']))
		{
			return false;
		}

		$key = $data['context'] . '_' . $data['item_id'];
		if (!isset(self::$_integration[$key]))
		{
			$topic_id = (!empty($data['topic_id'])) ? $data['topic_id'] : str_replace('.', '_', $key);

			$model = self::getTopicModel();
			$model->setState('topic.id', $topic_id);

			// Check state & access
			if (!empty($topic_id) && is_numeric($topic_id))
			{
				$user  = Factory::getUser();
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('id')
					->from('#__discussions_topics')
					->where('id = ' . (int) $topic_id);

				if (!$user->authorise('core.admin'))
				{
					$groups = implode(',', $user->getAuthorisedViewLevels());
					$query->where('access IN (' . $groups . ')');
				}

				$published = $model->getState('filter.published');
				if (!empty($published))
				{
					if (is_numeric($published))
					{
						$query->where('( state = ' . (int) $published .
							' OR (created_by > 0 AND created_by = ' . $user->id . ' AND state IN (0,1)))');
					}
					elseif (is_array($published))
					{
						$query->where('state IN (' . implode(',', $published) . ')');
					}
				}

				$db->setQuery($query);
				if (empty($db->loadResult()))
				{
					self::$_integration[$key] = false;

					return self::$_integration[$key];
				}
			}

			$items      = $model->getItems();
			$total      = $model->getTotal();
			$pagination = $model->getPagination();

			// Get the form.
			$addForm = $model->getAddPostForm($topic_id);
			if (empty($data['topic_id']) && !empty($data['create_topic']) && $addForm)
			{
				$addForm['form']->loadFile('create_topic');
				foreach ($data['create_topic'] as $name => $value)
				{
					$addForm['form']->setValue($name, 'create_topic', $value);
				}
			}

			$layoutData               = array();
			$layoutData['topic_id']   = $topic_id;
			$layoutData['items']      = $items;
			$layoutData['total']      = $total;
			$layoutData['pagination'] = $pagination;
			$layoutData['addForm']    = $addForm;

			$render = LayoutHelper::render('components.com_discussions.posts.list', $layoutData);

			$integration             = new stdClass();
			$integration->topic_id   = $topic_id;
			$integration->items      = $items;
			$integration->total      = $total;
			$integration->pagination = $pagination;
			$integration->addForm    = $addForm;
			$integration->layoutData = $layoutData;
			$integration->render     = $render;


			self::$_integration[$key] = $integration;

		}

		return self::$_integration[$key];
	}

	/**
	 * Method to update topic data
	 *
	 * @param array $data Save data
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public static function updateTopic($data)
	{
		if (!empty($data['context']) && !empty($data['item_id']))
		{
			$app = Factory::getApplication();

			// Load Language
			$language = Factory::getLanguage();
			$language->load('com_discussions', JPATH_SITE, $language->getTag());

			// Get topic id
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('id')
				->from('#__discussions_topics')
				->where('context = ' . $db->quote($data['context']))
				->where('item_id = ' . $data['item_id']);
			$db->setQuery($query);
			$data['id'] = $db->loadResult();

			if (!empty($data['id']))
			{
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_discussions/models', 'DiscussionsModel');
				$model = BaseDatabaseModel::getInstance('TopicForm', 'DiscussionsModel', array('ignore_request' => true));

				$form = $model->getForm($data, false);
				if (!$form)
				{
					$app->enqueueMessage(Text::_('COM_DISCUSSIONS_ERROR_CANT_SAVE_TOPIC'), 'warning');

					return false;
				}

				$validData = $model->validate($form, $data);
				if (!$validData)
				{
					$app->enqueueMessage(Text::_('COM_DISCUSSIONS_ERROR_CANT_SAVE_TOPIC'), 'warning');

					return false;
				}

				if (!$model->save($validData))
				{
					$app->enqueueMessage(Text::_('COM_DISCUSSIONS_ERROR_CANT_SAVE_TOPIC'), 'warning');

					return false;
				}
			}
		}

		$app->enqueueMessage(Text::_('COM_DISCUSSIONS_TOPIC_SAVE_SUCCESS'), 'message');

		return true;
	}

	/**
	 * Method to get Topic Model
	 *
	 * @return bool|JModelLegacy
	 *
	 * @since 1.0.0
	 */
	protected static function getTopicModel()
	{
		$app    = Factory::getApplication();
		$user   = Factory::getUser();
		$params = ComponentHelper::getParams('com_discussions');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_discussions/models', 'DiscussionsModel');
		$model = BaseDatabaseModel::getInstance('Topic', 'DiscussionsModel', array('ignore_request' => true));

		$model->setState('params', $params);
		// Published state
		$asset = 'com_discussions';
		if ((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset)))
		{
			// Limit to published for people who can't edit or edit.state.
			$model->setState('filter.published', 1);
		}
		else
		{
			$model->setState('filter.published', array(0, 1));
		}

		$model->setState('list.limit', $params->get('posts_limit', 10));
		$model->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

		return $model;
	}
}