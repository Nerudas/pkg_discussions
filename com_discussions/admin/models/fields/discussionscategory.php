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

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

class JFormFieldDiscussionsCategory extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'discussionsCategory';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions()
	{
		$app = Factory::getApplication();

		// Get categories
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('c.id', 'c.parent_id', 'c.title', 'c.level'))
			->from($db->quoteName('#__discussions_categories', 'c'))
			->where($db->quoteName('c.alias') . ' <>' . $db->quote('root'));

		if (!empty($this->element['maxlevel']))
		{
			$query->where($db->quoteName('c.level') . ' <=' . (int) $this->element['maxlevel']);
		}
		$query->order($db->escape('c.lft') . ' ' . $db->escape('asc'));

		$db->setQuery($query);
		$categories = $db->loadObjectList();

		// Prepare options
		$check     = false;
		$component = $app->input->get('option', 'com_discussions');
		$view      = $app->input->get('view', 'category');
		$id        = $app->input->getInt('id', 0);

		if ($app->isAdmin() && $component == 'com_discussions' && $view == 'category')
		{
			$check = true;
		}
		$options = parent::getOptions();
		foreach ($categories as $i => $category)
		{
			$option        = new stdClass();
			$option->value = $category->id;
			$option->text  = $category->title;
			if (empty($option->text))
			{
				$option->text = $category->alias;
			}

			if ($category->level > 1)
			{
				$option->text = str_repeat('- ', ($category->level - 1)) . $option->text;
			}

			if ($check && $id !== 0 && ($category->id == $id || $category->parent_id == $id))
			{
				$option->disable = true;
			}

			if ($id == 0 && $category->id = 1)
			{
				$option->selected = true;
			}

			$options[] = $option;
		}

		return $options;
	}
}