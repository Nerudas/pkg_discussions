<?php
/**
 * @package    Content - Tags Discussions Metadata Plugin
 * @version    1.2.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

JLoader::register('FieldTypesFilesHelper', JPATH_PLUGINS . '/fieldtypes/files/helper.php');

class plgContentTags_Discussions_Meta extends CMSPlugin
{
	/**
	 * Images root path
	 *
	 * @var    string
	 *
	 * @since  1.2.0
	 */
	protected $images_root = 'images/discussions/tags';

	/**
	 * Table name
	 *
	 * @var    string
	 *
	 * @since  1.2.0
	 */
	protected $table_name = '#__discussions_tags';

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.2.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Adds additional fields & rules to From
	 *
	 * @param   Joomla\CMS\Form\Form $form The form to be altered.
	 * @param   mixed                $data The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since  1.2.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		if ($form->getName() == 'com_tags.tag')
		{
			Form::addFormPath(__DIR__);
			$form->loadFile('form', true);

			// Set images folder root
			$form->setFieldAttribute('discussions_images_folder', 'root', $this->images_root);
		}

		return true;
	}

	/**
	 * Saves user  data
	 *
	 * @param   string $context The context of the content passed to the plugin (added in 1.6).
	 * @param   object $article A JTableContent object.
	 * @param   bool   $isNew   If the content is just about to be created.
	 *
	 * @return  void
	 *
	 * @since 1.2.0
	 */
	public function onContentAfterSave($context, $article, $isNew)
	{
		$id   = $article->id;
		$data = Factory::getApplication()->input->post->get('jform', array(), 'array');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName($this->table_name))
			->where('id =' . $id);
		$db->setQuery($query)->execute();

		$object           = new stdClass();
		$object->id       = $id;
		$object->metakey  = (!empty($data['discussions_metakey'])) ? $data['discussions_metakey'] : '';
		$object->metadesc = (!empty($data['discussions_metadesc'])) ? $data['discussions_metadesc'] : '';

		if (isset($data['discussions_metadata']) && is_array($data['discussions_metadata']))
		{
			$registry         = new Registry($data['discussions_metadata']);
			$object->metadata = $registry->toString('json', array('bitmask' => JSON_UNESCAPED_UNICODE));
		}

		$db->insertObject($this->table_name, $object);

		// Save images
		if ($isNew && !empty($data['discussions_images_folder']))
		{
			$filesHelper = new FieldTypesFilesHelper();
			$filesHelper->moveTemporaryFolder($data['discussions_images_folder'], $id, $this->images_root);
		}
	}

	/**
	 * Runs on content preparation
	 *
	 * @param   string $context The context for the data
	 * @param   object $data    An object containing the data for the form.
	 *
	 * @return  void
	 *
	 * @since 1.2.0
	 */
	public function onContentPrepareData($context, $data)
	{
		$app = Factory::getApplication();
		if ($app->isAdmin() && $app->input->get('option') == 'com_tags' && $context == 'com_tags.tag'
			&& is_object($data) && !isset($data->discussions_metakey) && !empty($data->id))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from($this->table_name)
				->where('id =' . $data->id);
			$db->setQuery($query);
			$object = $db->loadObject();

			if (!empty($object))
			{
				$data->discussions_metakey  = $object->metakey;
				$data->discussions_metadesc = $object->metadesc;

				$registry            = new Registry($object->metadata);
				$data->discussions_metadata = $registry->toArray();
			}
		}
	}

	/**
	 *Runs after content delete
	 *
	 * @param   string $context The context of the content passed to the plugin (added in 1.6).
	 * @param   object $article A JTableContent object.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onContentAfterDelete($context, $article)
	{
		if ($context == 'com_tags.tag')
		{
			$id    = $article->id;
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->delete($db->quoteName($this->table_name))
				->where('id =' . $id);
			$db->setQuery($query)->execute();

			$filesHelper = new FieldTypesFilesHelper();
			$filesHelper->deleteItemFolder($id, $this->images_root);
		}
	}
}