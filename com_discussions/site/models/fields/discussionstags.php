<?php
/**
 * @package    Discussions Component
 * @version    1.2.0
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
use Joomla\CMS\Router\Route;

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
	 * links as value
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	protected $links = false;

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement $element   The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed            $value     The form field value to validate.
	 * @param   string           $group     The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   1.0.0
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);
		if ($return)
		{
			$this->links = (!empty($this->element['links']) && (string) $this->element['links'] == 'true');
		}

		if ($this->links)
		{
			$this->name     = '';
			$this->value    = Route::_(DiscussionsHelperRoute::getTopicsRoute($this->value));
			$this->onchange = 'if (this.value) window.location.href=this.value';
		}

		return $return;
	}


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
		$root->value = Route::_(DiscussionsHelperRoute::getTopicsRoute(1));
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
				->where('t.id IN (' . implode(',', $tags) . ')');

			$user = Factory::getUser();
			if (!$user->authorise('core.admin'))
			{
				$query->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');
			}
			if (!$user->authorise('core.manage', 'com_tags'))
			{
				$query->where('t.published =  1');
			}

			$query->order($db->escape('t.lft') . ' ' . $db->escape('asc'));
			$db->setQuery($query);
			$objects = $db->loadObjectList();

			foreach ($objects as $i => $tag)
			{
				$id            = $tag->id;
				$option        = new stdClass();
				$option->text  = $tag->title;
				$option->value = Route::_(DiscussionsHelperRoute::getTopicsRoute($id));
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