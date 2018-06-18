<?php
/**
 * @package    Discussions Component
 * @version    1.0.4
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class DiscussionsViewTopics extends HtmlView
{
	/**
	 * Category object
	 *
	 * @var    object
	 *
	 * @since  1.0.0
	 */
	protected $topic;

	/**
	 * The link to add form
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $addLink;

	/**
	 * Child objects
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $children;

	/**
	 * Parent object
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	protected $parent;

	/**
	 * An array of items
	 *
	 * @var  array
	 *
	 * @since  1.0.0
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  JPagination
	 *
	 * @since  1.0.0
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 *
	 * @since  1.0.0
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  JForm
	 *
	 * @since  1.0.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 *
	 * @since  1.0.0
	 */
	public $activeFilters;

	/**
	 * Display the view
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @throws Exception
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		$this->state         = $this->get('State');
		$this->category      = $this->get('Category');
		$this->parent        = $this->get('Parent');
		$this->addLink       = $this->category->addLink;
		$this->link          = $this->category->link;
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Create a shortcut for category.
		$topic          = $this->category;
		$topic->parent  = $this->parent;
		$topic->addLink = $this->addLink;

		// Merge category params. If this is category view, menu params override category params
		// Otherwise, category params override menu item params
		$this->params = $this->state->get('params');
		$active       = $app->getMenu()->getActive();
		$temp         = clone $this->params;

		// Check to see which parameters should take priority
		if ($active)
		{
			$currentLink = $active->link;
			// If the current view is the active item and an category view for this category, then the menu item params take priority
			if (strpos($currentLink, 'view=topics') && strpos($currentLink, '&id=' . (string) $topic->id))
			{
				// Load layout from active query (in case it is an alternative menu item)
				if (isset($active->query['layout']))
				{
					$this->setLayout($active->query['layout']);
				}

				// Check for alternative layout of category
				elseif ($layout = $topic->params->get('topics_layout'))
				{
					$this->setLayout($layout);
				}

				// $topic->params are the category params, $temp are the menu item params
				// Merge so that the menu item params take priority
				$topic->params->merge($temp);
			}
			else
			{
				// Current view is not a single category, so the category params take priority here
				// Merge the menu item params with the category params so that the category params take priority
				$temp->merge($topic->params);
				$topic->params = $temp;

				// Check for alternative layouts (since we are not in a category menu item)
				// category menu item layout takes priority over alt layout for an category
				if ($layout = $topic->params->get('topics_layout'))
				{
					$this->setLayout($layout);
				}
			}
		}
		else
		{
			// Merge so that category params take priority
			$temp->merge($topic->params);
			$topic->params = $temp;

			// Check for alternative layouts (since we are not in a category menu item)
			// category menu item layout takes priority over alt layout for an category
			if ($layout = $topic->params->get('topics_layout'))
			{
				$this->setLayout($layout);
			}
		}

		/* Check for no 'access-view',
		 * - Redirect guest users to login
		 * - Deny access to logged users with 403 code
		 * NOTE: we do not recheck for no access-view + show_noauth disabled ... since it was checked above
		 */
		if ($topic->params->get('access-view') == false)
		{
			if ($user->get('guest'))
			{

				$login_url = Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()));
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
				$app->redirect($login_url, 403);
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return false;
			}
		}

		// Title for root category
		if ($active && $topic->root)
		{
			$topic->title = $active->title;
		}

		// Set search placeholder
		if ($topic->params->get('search_placeholder', ''))
		{
			$this->filterForm->setFieldAttribute('search', 'hint', $topic->params->get('search_placeholder'), 'filter');
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->category->params->get('pageclass_sfx'));

		$this->_prepareDocument();

		return parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function _prepareDocument()
	{
		$app      = Factory::getApplication();
		$pathway  = $app->getPathway();
		$topic    = $this->category;
		$url      = rtrim(URI::root(), '/') . $topic->link;
		$sitename = $app->get('sitename');
		$menus    = $app->getMenu();
		$menu     = $menus->getActive();
		$id       = (int) @$menu->query['id'];

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_DISCUSSIONS_CATEGORY'));
		}
		$title = $this->params->get('page_title', $sitename);

		// If the menu item does not concern this contact
		if ($menu && ($menu->query['option'] !== 'com_discussions' || $menu->query['view'] !== 'topics' || $id != $topic->id))
		{
			if ($topic->title)
			{
				$title = $topic->title;
			}

			$path   = array();
			$path[] = array('title' => $title, 'link' => '');

			$parent = $topic->parent;
			while ($parent && $parent->id > 1 &&
				($menu->query['option'] !== 'com_discussions' || $menu->query['view'] === 'topics' || $id != $parent->id))
			{
				$path[] = array('title' => $parent->title, 'link' => $parent->link);
				$parent = $this->getModel()->getParent($parent->id);
			}

			foreach (array_reverse($path) as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}
		}

		// Set pathway title
		$title = array();
		foreach ($pathway->getPathWay() as $item)
		{
			$title[] = $item->name;
		}
		$title = implode(' / ', $title);

		if ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $sitename, $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $sitename);
		}

		// Set Meta Title
		$this->document->setTitle($title);

		// Set Meta Description
		if (!empty($topic->metadesc))
		{
			$this->document->setDescription($topic->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		// Set Meta Keywords
		if (!empty($topic->metakey))
		{
			$this->document->setMetadata('keywords', $topic->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// Set Meta Robots
		if ($topic->metadata->get('robots', ''))
		{
			$this->document->setMetadata('robots', $topic->metadata->get('robots', ''));
		}
		elseif ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}

		// Set Meta Author
		if ($app->get('MetaAuthor') == '1' && $topic->metadata->get('author', ''))
		{
			$this->document->setMetaData('author', $topic->metadata->get('author'));
		}

		// Set Meta Rights
		if ($topic->metadata->get('rights', ''))
		{
			$this->document->setMetaData('author', $topic->metadata->get('rights'));
		}

		// Set Meta Image
		if ($topic->metadata->get('image', ''))
		{
			$this->document->setMetaData('image', URI::base() . $topic->metadata->get('image'));
		}
		elseif ($this->params->get('menu-meta_image', ''))
		{
			$this->document->setMetaData('image', Uri::base() . $this->params->get('menu-meta_image'));
		}

		// Set Meta twitter
		$this->document->setMetaData('twitter:card', 'summary_large_image');
		$this->document->setMetaData('twitter:site', $sitename);
		$this->document->setMetaData('twitter:creator', $sitename);
		$this->document->setMetaData('twitter:title', $this->document->getTitle());
		if ($this->document->getMetaData('description'))
		{
			$this->document->setMetaData('twitter:description', $this->document->getMetaData('description'));
		}
		if ($this->document->getMetaData('image'))
		{
			$this->document->setMetaData('twitter:image', $this->document->getMetaData('image'));
		}
		$this->document->setMetaData('twitter:url', $url);

		// Set Meta Open Graph
		$this->document->setMetadata('og:type', 'website', 'property');
		$this->document->setMetaData('og:site_name', $sitename, 'property');
		$this->document->setMetaData('og:title', $this->document->getTitle(), 'property');
		if ($this->document->getMetaData('description'))
		{
			$this->document->setMetaData('og:description', $this->document->getMetaData('description'), 'property');
		}
		if ($this->document->getMetaData('image'))
		{
			$this->document->setMetaData('og:image', $this->document->getMetaData('image'), 'property');
		}
		$this->document->setMetaData('og:url', $url, 'property');
	}
}