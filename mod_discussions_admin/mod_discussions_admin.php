<?php
/**
 * @package    Discussions - Administrator Module
 * @version    1.0.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

$language = Factory::getLanguage();
$language->load('com_discussions', JPATH_ADMINISTRATOR, $language->getTag(), true);

require ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default'));