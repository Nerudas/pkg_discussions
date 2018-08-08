<?php
/**
 * @package    Discussions Component
 * @version    1.1.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;

class DiscussionsHelper extends ContentHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(Text::_('COM_DISCUSSIONS_POSTS'),
			'index.php?option=com_discussions&view=posts',
			$vName == 'posts');

		JHtmlSidebar::addEntry(Text::_('COM_DISCUSSIONS_TOPICS'),
			'index.php?option=com_discussions&view=topics',
			$vName == 'topics');
	}
}