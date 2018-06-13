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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Table\Table;

class DiscussionsModelCategory extends AdminModel
{
	/**
	 * Imagefolder helper helper
	 *
	 * @var    new imageFolderHelper
	 *
	 * @since  1.0.0
	 */
	protected $imageFolderHelper = null;

	/**
	 * Constructor.
	 *
	 * @param   array $config An optional associative array of configuration settings.
	 *
	 * @see     AdminModel
	 *
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
		$this->imageFolderHelper = new imageFolderHelper('images/discussions/categories');

		parent::__construct($config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer $pk The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since  1.0.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry       = new Registry($item->metadata);
			$item->metadata = $registry->toArray();

			// Convert the attribs field to an array.
			$registry      = new Registry($item->attribs);
			$item->attribs = $registry->toArray();

			// Get Tags
			$item->tags = new TagsHelper;
			$item->tags->getTagIds($item->id, 'com_discussions.category');

			$item->published = $item->state;
		}

		return $item;
	}


	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 * @since  1.0.0
	 */
	public function getTable($type = 'Categories', $prefix = 'DiscussionsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since  1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$app  = Factory::getApplication();
		$form = $this->loadForm('com_discussions.category', 'category', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}
		/*
		 * The front end calls this model and uses a_id to avoid id clashes so we need to check for that first.
		 * The back end uses id so we use that the rest of the time and set it to 0 by default.
		 */
		$id   = ($this->getState('category.id')) ? $this->getState('category.id') : $app->input->get('id', 0);
		$user = Factory::getUser();
		// Check for existing category.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_discussions.category.' . (int) $id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_discussions')))
		{
			// Disable fields for display.
			$form->setFieldAttribute('state', 'disabled', 'true');
			// Disable fields while saving.
			// The controller has already verified this is an category you can edit.
			$form->setFieldAttribute('state', 'filter', 'unset');
		}

		// Set update icon link
		$form->setFieldAttribute('icon', 'saveurl', 'index.php?option=com_discussions&task=category.updateImages&field=icon&id=' . $id);

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since  1.0.0
	 */
	protected function loadFormData()
	{
		$app  = Factory::getApplication();
		$data = Factory::getApplication()->getUserState('com_discussions.edit.category.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
			// Pre-select some filters (Status,  Language, Access) in edit form if those have been selected in category Manager: Pages
			if ($this->getState('category.id') == 0)
			{
				$filters = (array) $app->getUserState('com_discussions.categories.filter');
				$data->set('access',
					$app->input->getInt('access', (!empty($filters['access']) ? $filters['access'] : Factory::getConfig()->get('access')))
				);
			}
		}
		$this->preprocessData('com_discussions.category', $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  1.0.0
	 */
	public function save($data)
	{
		$app        = Factory::getApplication();
		$pk         = (!empty($data['id'])) ? $data['id'] : (int) $this->getState($this->getName() . '.id');
		$table      = $this->getTable();
		$isNew      = true;
		$context    = $this->option . '.' . $this->name;
		$dispatcher = JEventDispatcher::getInstance();

		if (!empty($data['tags']) && $data['tags'][0] != '')
		{
			$table->newTags = $data['tags'];
		}


		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		if (isset($data['metadata']) && is_array($data['metadata']))
		{
			$registry         = new Registry($data['metadata']);
			$data['metadata'] = (string) $registry;
		}

		// Load the row if saving an existing type.
		if ($pk > 0)
		{
			$table->load($pk);
			$isNew = false;
		}
		// Set the new parent id if parent id not matched OR while New .
		if ($table->parent_id != $data['parent_id'] || $data['id'] == 0)
		{
			$table->setLocation($data['parent_id'], 'last-child');
		}

		// Check alias
		$alias = (!empty($data['alias']) && !in_array($data['alias'], array('form', 'add', 'edit'))) ? $data['alias'] : $data['title'];
		if (Factory::getConfig()->get('unicodeslugs') == 1)
		{
			$alias = JFilterOutput::stringURLUnicodeSlug($alias);
		}
		else
		{
			$alias = JFilterOutput::stringURLSafe($alias);
		}

		$checkAlias = $this->getTable();
		$checkAlias->load(array('alias' => $alias, 'parent_id' => $data['parent_id']));
		if (!empty($checkAlias->id) && ($checkAlias->id != $pk || $isNew))
		{
			$msg   = Text::_('COM_DISCUSSIONS_CATEGORY_SAVE_WARNING');
			$alias = $this->generateNewAlias($data['parent_id'], $alias);
			$app->enqueueMessage($msg, 'warning');
		}
		$data['alias'] = $alias;

		// Get tags search
		if (!empty($data['tags']))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('id', 'title'))
				->from('#__tags')
				->where('id IN (' . implode(',', $data['tags']) . ')');
			$db->setQuery($query);
			$tags = $db->loadObjectList();

			$tags_search = array();
			$tags_map    = array();
			$items_tags  = array();
			foreach ($tags as $tag)
			{
				$tags_search[$tag->id] = $tag->title;
				$tags_map[$tag->id]    = '[' . $tag->id . ']';
				$items_tags[$tag->id]  = $tag->id;
			}

			$data['tags_search'] = implode(', ', $tags_search);
			$data['tags_map']    = implode('', $tags_map);
			$data['items_tags']  = implode(',', $items_tags);
		}
		else
		{
			$data['tags_search'] = '';
			$data['tags_map']    = '';
			$data['items_tags']  = '';
		}

		// Prepare attribs json
		if (isset($data['attribs']) && is_array($data['attribs']))
		{
			$registry        = new Registry($data['attribs']);
			$data['attribs'] = (string) $registry;
		}

		// Bind the data.
		if (!$table->bind($data))
		{
			$this->setError($table->getError());

			return false;
		}

		// Check the data.
		if (!$table->check())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the before save event.
		$result = $dispatcher->trigger($this->event_before_save, array($context, &$table, $isNew, $data));
		if (in_array(false, $result, true))
		{
			$this->setError($table->getError());

			return false;
		}

		// Store the data.
		if (!$table->store())
		{
			$this->setError($table->getError());

			return false;
		}

		// Trigger the after save event.
		$dispatcher->trigger($this->event_after_save, array($context, &$table, $isNew, $data));

		// Rebuild the path for the type:
		if (!$table->rebuildPath($table->id))
		{
			$this->setError($table->getError());

			return false;
		}

		// Rebuild the paths of the types children:
		if (!$table->rebuild($table->id, $table->lft, $table->level, $table->path))
		{
			$this->setError($table->getError());

			return false;
		}

		$this->setState($this->getName() . '.id', $table->id);

		// Clear the cache
		$this->cleanCache();

		$id = $table->id;

		// Save images
		$data['imagefolder'] = (!empty($data['imagefolder'])) ? $data['imagefolder'] :
			$this->imageFolderHelper->getItemImageFolder($id);
		if (isset($data['icon']))
		{
			$this->imageFolderHelper->saveItemImages($id, $data['imagefolder'], '#__discussions_categories', 'icon', $data['icon']);
		}

		return true;
	}

	/**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array   $idArray   An array of primary key ids.
	 * @param   integer $lft_array The lft value
	 *
	 * @return  boolean  False on failure or error, True otherwise
	 *
	 * @since  1.0.0
	 */
	public function saveorder($idArray = null, $lft_array = null)
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if (!$table->saveorder($idArray, $lft_array))
		{
			$this->setError($table->getError());

			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to change the alias.
	 *
	 * @param   integer $parent_id The id of the parent.
	 * @param   string  $alias     The alias.
	 *
	 * @return  string  Contains the modified title and alias.
	 *
	 * @since  1.0.0
	 */
	protected function generateNewAlias($parent_id, $alias)
	{
		$table = $this->getTable();
		while ($table->load(array('alias' => $alias, 'parent_id' => $parent_id)))
		{
			$alias = StringHelper::increment($alias, 'dash');
		}

		return $alias;
	}


	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since  1.0.0
	 */
	public function delete(&$pks)
	{
		if (parent::delete($pks))
		{
			// Delete images
			foreach ($pks as $pk)
			{
				$this->imageFolderHelper->deleteItemImageFolder($pk);
			}

			return true;
		}

		return false;
	}
}