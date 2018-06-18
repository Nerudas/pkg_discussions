<?php
/**
 * @package    Discussions Package
 * @version    1.0.4
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


class Pkg_discussionsInstallerScript
{
	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	function postflight()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('extension_id')
			->from('#__extensions')
			->where($db->quoteName('element') . ' = ' . $db->quote('mod_discussions_categories'));
		$db->setQuery($query);
		$eid = $db->loadResult();
		if ($eid > 0)
		{
			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_installer/models');
			$model = BaseDatabaseModel::getInstance('Manage', 'InstallerModel', array('ignore_request' => true));
			$model->remove(array($eid));
		}

		return true;
	}
}