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

use Joomla\CMS\Helper\RouteHelper;

class DiscussionsHelperRoute extends RouteHelper
{
	/**
	 * Fetches the topics route
	 *
	 * @param int $tag_id Tag id
	 *
	 * @return  string
	 *
	 * @since  1.0.0n
	 */
	public static function getTopicsRoute($tag_id = 1)
	{
		return 'index.php?option=com_discussions&view=topics&id=' . $tag_id;
	}

	/**
	 * Fetches the topic route
	 *
	 * @param   int $id Item ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public static function getTopicRoute($id = null)
	{
		$link = 'index.php?option=com_discussions&view=topic&tag_id=1';

		if (!empty($id))
		{
			$link .= '&id=' . $id;
		}

		return $link;
	}

	/**
	 * Fetches the form route
	 *
	 * @param  int $id     Item ID
	 * @param int  $tag_id Tag ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public static function getTopicFormRoute($id = null, $tag_id = 1)
	{
		$link = 'index.php?option=com_discussions&view=topicform&tag_id=' . $tag_id;
		if (!empty($id))
		{
			$link .= '&id=' . $id;
		}

		return $link;
	}

	/**
	 * Fetches the form route
	 *
	 * @param  int $id       Item ID
	 * @param  int $topic_id Topic ID
	 * @param int  $tag_id Tag ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public static function getPostFormRoute($id = null, $topic_id, $tag_id = 1)
	{
		$link = 'index.php?option=com_discussions&view=postform&topic_id=' . $topic_id . '&tag_id=' . $tag_id;
		if (!empty($id))
		{
			$link .= '&id=' . $id;
		}

		return $link;
	}
}