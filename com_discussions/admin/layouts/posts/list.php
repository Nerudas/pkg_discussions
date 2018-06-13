<?php
/**
 * @package    Discussions Component
 * @version    1.0.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

if (!empty($items))
{
	foreach ($items as $item)
	{
		echo LayoutHelper::render('components.com_discussions.posts.item', $item);
	}
	echo $pagination->getListFooter();
}
echo LayoutHelper::render('components.com_discussions.posts.form', $addForm);
