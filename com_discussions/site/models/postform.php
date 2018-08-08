<?php
/**
 * @package    Discussions Component
 * @version    1.1.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

JLoader::register('DiscussionsModelPost', JPATH_ADMINISTRATOR . '/components/com_discussions/models/post.php');

class DiscussionsModelPostForm extends DiscussionsModelPost
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		// Load state from the request.
		$pk = $app->input->getInt('id', 0);
		$this->setState('post.id', $pk);

		// Load state from the request.
		$topic_id = $app->input->getInt('topic_id', 0);
		$this->setState('topic.id', $topic_id);

		$return = $app->input->get('return', null, 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);

		parent::populateState();
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
		$id = $this->getState('post.id', Factory::getApplication()->input->get('id', 0));

		Form::addFormPath(JPATH_SITE . '/components/com_discussions/models/forms');
		Form::addFieldPath(JPATH_SITE . '/components/com_discussions/models/fields');
		Form::addFormPath(JPATH_SITE . '/components/com_discussions/model/form');
		Form::addFieldPath(JPATH_SITE . '/components/com_discussions/model/field');

		$form = $this->loadForm('com_discussions.post.' . $id, 'post', array('control' => 'jform_post_' . $id, 'load_data' => $loadData));
		if ($form)
		{
			if (!Factory::getUser()->guest)
			{
				$form->removeField('captcha');
			}
		}

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
		$pk = $this->getState('post.id', Factory::getApplication()->input->get('id', 0));
		if (empty($pk))
		{
			$pk = $this->getState('topic.id', $app->input->getInt('topic_id', 0)) . '_0';
		}
		$data = Factory::getApplication()->getUserState('com_discussions.edit.post.data.' . $pk, array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_discussions.post', $data);

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
		$app = Factory::getApplication();

		$data['id'] = (isset($data['id'])) ? $data['id'] : 0;

		$app->input->set('post_id', $data['id']);
		$app->input->set('topic_id', $data['topic_id']);

		if ($id = parent::save($data))
		{
			$app->input->set('post_id', $id);

			return $id;
		}

		return false;
	}

	/**
	 * Get the return URL.
	 *
	 * @return  string    The return URL.
	 *
	 * @since  1.0.0
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

}