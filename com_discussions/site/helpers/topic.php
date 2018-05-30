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

		return BaseDatabaseModel::getInstance('Topic', 'DiscussionsModel', array('ignore_request' => true));
	}
}