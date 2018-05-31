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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

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
	 * Method to get Topic Model
	 *
	 * @return bool|JModelLegacy
	 *
	 * @since 1.0.0
	 */
	protected static function getTopicModel()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_discussions/models', 'DiscussionsModel');

		$model = BaseDatabaseModel::getInstance('Topic', 'DiscussionsModel', array('ignore_request' => true));
		$user  = Factory::getUser();

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

		return $model;
	}
}