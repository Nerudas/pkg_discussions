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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

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

		$app       = Factory::getApplication();
		$component = $app->input->get('option');
		if (empty($this->value) && $app->isSite() && $component == 'com_discussions')
		{
			$this->value = $app->input->get('id', 1);
		}

		if ($this->links)
		{
			$this->name     = '';
			$this->value    = Route::_('index.php?option=com_discussions&view=topics&id=' . $this->value);
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
		$access = Factory::getUser()->getAuthorisedViewLevels();

		// Get categories
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(array('c.id', 'c.parent_id', 'c.title', 'c.level'))
			->from($db->quoteName('#__discussions_categories', 'c'))
			->where($db->quoteName('c.alias') . ' <>' . $db->quote('root'))
			->where('c.state =  1')
			->where('c.access IN (' . implode(',', $access) . ')');


		$query->order($db->escape('c.lft') . ' ' . $db->escape('asc'));

		$db->setQuery($query);
		$categories = $db->loadObjectList();

		$root        = new stdClass();
		$root->title = Text::_('COM_DISCUSSIONS_CATEGORY_ROOT');
		$root->id    = 1;
		$root->level = 0;
		array_unshift($categories, $root);

		$options = parent::getOptions();


		foreach ($categories as $i => $category)
		{
			$option        = new stdClass();
			$option->value = (!$this->links) ? $category->id :
				Route::_('index.php?option=com_discussions&view=topics&id=' . $category->id);

			$option->text = $category->title;
			if ($category->level > 1)
			{
				$option->text = str_repeat('- ', ($category->level - 1)) . $option->text;
			}
			if (empty($option->text))
			{
				$option->text = $category->alias;
			}
			if ($this->value == $category->id)
			{
				$option->selected = true;
			}

			$options[] = $option;
		}

		return $options;
	}
}
