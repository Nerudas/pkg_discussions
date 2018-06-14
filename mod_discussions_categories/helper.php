<?php
/**
 * @package    Discussions - Categories Module
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;


class modDiscussionsCategoriesHelper
{


	/**
	 * Method to get categories data.
	 *
	 * @param  \Joomla\Registry\Registry $params module params
	 *
	 * @return bool|object
	 *                  - root First level categories
	 *                  - children children array
	 * @since  1.0.0
	 */
	public static function getCategories($params)
	{
		$app    = Factory::getApplication();
		$access = Factory::getUser()->getAuthorisedViewLevels();

		// Get category items
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('id', 'parent_id', 'title', 'level', 'icon'))
			->from($db->quoteName('#__discussions_categories'))
			->where($db->quoteName('alias') . ' <>' . $db->quote('root'))
			->where('state =  1')
			->where('access IN (' . implode(',', $access) . ')')
			->order($db->escape('lft') . ' ' . $db->escape('asc'));
		$db->setQuery($query);
		$categories = $db->loadObjectList('id');

		if (empty($categories))
		{
			return false;
		}

		$checkView = ($app->input->get('option') == 'com_discussions' && $app->input->get('view') == 'topics');
		$children  = array();
		$root      = array();
		foreach ($categories as $id => &$category)
		{
			$category->link = Route::_(DiscussionsHelperRoute::getTopicsRoute($id));

			$category->active = ($checkView && $app->input->get('id') == $category->id);

			if ($category->level == 1)
			{
				$category->activeParent = false;
			}
			elseif ($category->active)
			{
				$categories[$category->parent_id]->activeParent = true;
			}

			if ($category->level == 1)
			{
				$root[$id] = $category;
			}

			if (!isset($children[$id]))
			{
				$children[$id] = array();
			}

			if ($category->parent_id > 1)
			{
				if (!isset($children[$category->parent_id]))
				{
					$children[$category->parent_id] = array();
				}

				$children[$category->parent_id][$id] = $category;
			}
		}

		$return           = new stdClass();
		$return->all      = $categories;
		$return->root     = $root;
		$return->children = $children;

		return $return;
	}
}