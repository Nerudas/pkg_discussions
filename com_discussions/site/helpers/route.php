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

use Joomla\CMS\Helper\RouteHelper;

class DiscussionsHelperRoute extends RouteHelper
{
	/**
	 * Fetches the list route
	 *
	 * @param   int $catid Category ID
	 *
	 * @return  string
	 *
	 * @since  1.0.0n
	 */
	public static function getListRoute($catid = 1)
	{
		return 'index.php?option=com_discussions&view=list&id=' . $catid;
	}

}