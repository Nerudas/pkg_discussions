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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class DiscussionsControllerPost extends FormController
{

	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $view_list = 'topic';

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $text_prefix = 'COM_DISCUSSIONS_POST';

	/**
	 * Method to save a record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since  1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();

		// Save post
		$pk   = $app->input->getInt('id', $app->input->getInt('topic_id', 0) . '_0');
		$data = $this->input->post->get('jform_post_' . $pk, array(), 'array');

		$app->input->post->set('jform', $data);
		parent::save($key, $urlVar);

		// Get return
		$post_id  = $app->input->getInt('post_id', $data['id']);
		$topic_id = $app->input->getInt('topic_id', $data['topic_id']);
		$return   = base64_decode($app->input->get('return', null, 'base64'));
		if (empty($return))
		{
			$return = Route::_(DiscussionsHelperRoute::getTopicRoute($topic_id));
		}

		$uri = Uri::getInstance($return);
		$uri->delVar('post_id');
		$uri->delVar('start');
		$uri->delVar('limitstart');
		$uri->setVar('post_id', $post_id);

		$this->setRedirect($uri->toString());

		return $result;
	}

	/**
	 * Method to check if you can edit an existing record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array  $data An array of input data.
	 * @param   string $key  The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		$user     = Factory::getUser();
		$selector = (!empty($data[$key])) ? $data[$key] : 0;
		$author   = (!empty($data['created_by'])) ? $data['created_by'] : 0;
		$canEdit  = $user->authorise('core.edit', 'com_discussions.post.' . $selector) ||
			($user->authorise('core.edit.own', 'com_discussions.post.' . $selector)
				&& !$user->guest && $author == $user->id);

		return $canEdit;
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array $data An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	protected function allowAdd($data = array())
	{

		return (parent::allowAdd($data) || Factory::getUser()->authorise('post.create', 'com_discussions.post'));
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string $name   The model name. Optional.
	 * @param   string $prefix The class prefix. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'PostForm', $prefix = 'DiscussionsModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

}