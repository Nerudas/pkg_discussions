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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class com_DiscussionsInstallerScript
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
		$path = '/components/com_discussions';

		$this->fixTables($path);
		$this->tagsIntegration();
		$this->createImageFolder();
		$this->moveLayouts($path);
		$this->createRootCategory();

		return true;
	}

	/**
	 * Create or image folders
	 *
	 * @since 1.0.0
	 */
	protected function createImageFolder()
	{
		$folders = array(
			JPATH_ROOT . '/images/discussions',
			JPATH_ROOT . '/images/discussions/categories',
			JPATH_ROOT . '/images/discussions/topics',
		);
		foreach ($folders as $folder)
		{
			if (!JFolder::exists($folder))
			{
				JFolder::create($folder);
				JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
			}
		}
	}

	/**
	 * Create root category
	 *
	 * @since  1.0.0
	 */
	protected function createRootCategory()
	{
		$db = Factory::getDbo();

		// Category
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__discussions_categories'))
			->where($db->quoteName('id') . ' = ' . $db->quote(1));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$root            = new stdClass();
		$root->id        = 1;
		$root->parent_id = 0;
		$root->lft       = 0;
		$root->rgt       = 1;
		$root->level     = 0;
		$root->path      = '';
		$root->alias     = 'root';
		$root->access    = 1;
		$root->state     = 1;

		(!empty($current_id)) ? $db->updateObject('#__discussions_categories', $root, 'id')
			: $db->insertObject('#__discussions_categories', $root);
	}


	/**
	 * Create or update tags integration
	 *
	 * @since 1.0.0
	 */
	protected function tagsIntegration()
	{
		$db = Factory::getDbo();

		// Category
		$query = $db->getQuery(true)
			->select('type_id')
			->from($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_discussions.category'));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$category                                               = new stdClass();
		$category->type_id                                      = (!empty($current_id)) ? $current_id : '';
		$category->type_title                                   = 'Discussions Category';
		$category->type_alias                                   = 'com_discussions.category';
		$category->table                                        = new stdClass();
		$category->table->special                               = new stdClass();
		$category->table->special->dbtable                      = '#__discussions_categories';
		$category->table->special->key                          = 'id';
		$category->table->special->type                         = 'Categories';
		$category->table->special->prefix                       = 'DiscussionsTable';
		$category->table->special->config                       = 'array()';
		$category->table->common                                = new stdClass();
		$category->table->common->dbtable                       = '#__ucm_content';
		$category->table->common->key                           = 'ucm_id';
		$category->table->common->type                          = 'Corecontent';
		$category->table->common->prefix                        = 'JTable';
		$category->table->common->config                        = 'array()';
		$category->table                                        = json_encode($category->table);
		$category->rules                                        = '';
		$category->field_mappings                               = new stdClass();
		$category->field_mappings->common                       = new stdClass();
		$category->field_mappings->common->core_content_item_id = 'id';
		$category->field_mappings->common->core_title           = 'title';
		$category->field_mappings->common->core_state           = 'state';
		$category->field_mappings->common->core_alias           = 'alias';
		$category->field_mappings->common->core_created_time    = 'null';
		$category->field_mappings->common->core_modified_time   = 'null';
		$category->field_mappings->common->core_body            = 'null';
		$category->field_mappings->common->core_hits            = 'null';
		$category->field_mappings->common->core_publish_up      = 'null';
		$category->field_mappings->common->core_publish_down    = 'null';
		$category->field_mappings->common->core_access          = 'access';
		$category->field_mappings->common->core_params          = 'attribs';
		$category->field_mappings->common->core_featured        = 'null';
		$category->field_mappings->common->core_metadata        = 'metadata';
		$category->field_mappings->common->core_language        = 'null';
		$category->field_mappings->common->core_images          = 'null';
		$category->field_mappings->common->core_urls            = 'null';
		$category->field_mappings->common->core_version         = 'null';
		$category->field_mappings->common->core_ordering        = 'lft';
		$category->field_mappings->common->core_metakey         = 'metakey';
		$category->field_mappings->common->core_metadesc        = 'metadesc';
		$category->field_mappings->common->core_catid           = 'null';
		$category->field_mappings->common->core_xreference      = 'null';
		$category->field_mappings->common->asset_id             = 'null';
		$category->field_mappings->special                      = new stdClass();
		$category->field_mappings                               = json_encode($category->field_mappings);
		$category->router                                       = 'DiscussionsHelperRoute::getTopicsRoute';
		$category->content_history_options                      = '';

		(!empty($current_id)) ? $db->updateObject('#__content_types', $category, 'type_id')
			: $db->insertObject('#__content_types', $category);

		// Topic
		$query = $db->getQuery(true)
			->select('type_id')
			->from($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_discussions.topic'));
		$db->setQuery($query);
		$current_id = $db->loadResult();

		$topic                                               = new stdClass();
		$topic->type_id                                      = (!empty($current_id)) ? $current_id : '';
		$topic->type_title                                   = 'Discussions topic';
		$topic->type_alias                                   = 'com_discussions.topic';
		$topic->table                                        = new stdClass();
		$topic->table->special                               = new stdClass();
		$topic->table->special->dbtable                      = '#__discussions_topics';
		$topic->table->special->key                          = 'id';
		$topic->table->special->type                         = 'Items';
		$topic->table->special->prefix                       = 'DiscussionsTable';
		$topic->table->special->config                       = 'array()';
		$topic->table->common                                = new stdClass();
		$topic->table->common->dbtable                       = '#__ucm_content';
		$topic->table->common->key                           = 'ucm_id';
		$topic->table->common->type                          = 'Corecontent';
		$topic->table->common->prefix                        = 'JTable';
		$topic->table->common->config                        = 'array()';
		$topic->table                                        = json_encode($topic->table);
		$topic->rules                                        = '';
		$topic->field_mappings                               = new stdClass();
		$topic->field_mappings->common                       = new stdClass();
		$topic->field_mappings->common->core_content_item_id = 'id';
		$topic->field_mappings->common->core_title           = 'title';
		$topic->field_mappings->common->core_state           = 'state';
		$topic->field_mappings->common->core_alias           = 'null';
		$topic->field_mappings->common->core_created_time    = 'null';
		$topic->field_mappings->common->core_modified_time   = 'null';
		$topic->field_mappings->common->core_body            = 'text';
		$topic->field_mappings->common->core_hits            = 'hits';
		$topic->field_mappings->common->core_publish_up      = 'null';
		$topic->field_mappings->common->core_publish_down    = 'null';
		$topic->field_mappings->common->core_access          = 'access';
		$topic->field_mappings->common->core_params          = 'null';
		$topic->field_mappings->common->core_featured        = 'null';
		$topic->field_mappings->common->core_metadata        = 'metadata';
		$topic->field_mappings->common->core_language        = 'null';
		$topic->field_mappings->common->core_images          = 'images';
		$topic->field_mappings->common->core_urls            = 'null';
		$topic->field_mappings->common->core_version         = 'null';
		$topic->field_mappings->common->core_ordering        = 'created';
		$topic->field_mappings->common->core_metakey         = 'metakey';
		$topic->field_mappings->common->core_metadesc        = 'metadesc';
		$topic->field_mappings->common->core_catid           = 'null';
		$topic->field_mappings->common->core_xreference      = 'null';
		$topic->field_mappings->common->asset_id             = 'null';
		$topic->field_mappings->special                      = new stdClass();
		$topic->field_mappings->special->region              = 'region';
		$topic->field_mappings                               = json_encode($topic->field_mappings);
		$topic->router                                       = 'DiscussionsHelperRoute::getTopicRoute';
		$topic->content_history_options                      = '';

		(!empty($current_id)) ? $db->updateObject('#__content_types', $topic, 'type_id')
			: $db->insertObject('#__content_types', $topic);
	}

	/**
	 * Move layouts folder
	 *
	 * @param string $path path to files
	 *
	 * @since 1.0.0
	 */
	protected function moveLayouts($path)
	{
		$component = JPATH_ADMINISTRATOR . $path . '/layouts';
		$layouts   = JPATH_ROOT . '/layouts' . $path;
		if (!JFolder::exists(JPATH_ROOT . '/layouts/components'))
		{
			JFolder::create(JPATH_ROOT . '/layouts/components');
		}
		if (JFolder::exists($layouts))
		{
			JFolder::delete($layouts);
		}
		JFolder::move($component, $layouts);
	}

	/**
	 *
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance $adapter The object responsible for running this script
	 *
	 * @since 1.0.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		$db = Factory::getDbo();

		// Remove content_type
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_discussions.category'));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__content_types'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_discussions.topic'));
		$db->setQuery($query)->execute();

		// Remove tag_map
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_discussions.category'));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__contentitem_tag_map'))
			->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_discussions.topic'));
		$db->setQuery($query)->execute();

		// Remove ucm_content
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__ucm_content'))
			->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_discussions.category'));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__ucm_content'))
			->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_discussions.topic'));
		$db->setQuery($query)->execute();

		// Remove images
		JFolder::delete(JPATH_ROOT . '/images/discussions');

		// Remove layouts
		JFolder::delete(JPATH_ROOT . '/layouts/components/com_discussions');
	}

	/**
	 * Method to fix tables
	 *
	 * @param string $path path to component directory
	 *
	 * @since 1.0.0
	 */
	protected function fixTables($path)
	{
		$file = JPATH_ADMINISTRATOR . $path . '/sql/install.mysql.utf8.sql';
		if (!empty($file))
		{
			$sql = JFile::read($file);

			if (!empty($sql))
			{
				$db      = Factory::getDbo();
				$queries = $db->splitSql($sql);
				foreach ($queries as $query)
				{
					$db->setQuery($db->convertUtf8mb4QueryToUtf8($query));
					try
					{
						$db->execute();
					}
					catch (JDataBaseExceptionExecuting $e)
					{
						JLog::add(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()),
							JLog::WARNING, 'jerror');
					}
				}
			}
		}
	}
}