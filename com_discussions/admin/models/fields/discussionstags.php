<?php
/**
 * @package    Discussions Component
 * @version    1.1.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

class JFormFieldDiscussionsTags extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'discussionsTags';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since  1.0.0
	 */
	protected function getOptions()
	{
		$params  = ComponentHelper::getParams('com_discussions');
		$tags    = $params->get('tags');
		$options = parent::getOptions();

		// Root
		$root        = new stdClass();
		$root->text = Text::_($params->get('root_title', 'COM_DISCUSSIONS_TOPICS_ALL'));
		$root->value    = 1;
		if ($this->value == $root->value)
		{
			$root->selected = true;
		}
		$options[] = $root;

		if (!empty($tags) && is_array($tags))
		{
			// Get tags
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('t.id', 't.title'))
				->from($db->quoteName('#__tags', 't'))
				->where($db->quoteName('t.alias') . ' <>' . $db->quote('root'))
				->where('t.id IN (' . implode(',', $tags) . ')')
				->order($db->escape('t.lft') . ' ' . $db->escape('asc'));
			$db->setQuery($query);
			$objects = $db->loadObjectList();

			foreach ($objects as $i => $tag)
			{
				$option        = new stdClass();
				$option->text  = $tag->title;
				$option->value = $tag->id;
				if ($option->value == $this->value)
				{
					$option->selected = true;
				}
				$options[] = $option;
			}
		}

		return $options;
	}
}