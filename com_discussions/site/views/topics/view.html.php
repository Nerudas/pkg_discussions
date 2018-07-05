<?php
/**
 * @package    Discussions Component
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
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
		$app = Factory::getApplication();

		$this->state         = $this->get('State');
		$this->tag           = $this->get('Tag');
		$this->addLink       = $this->tag->addLink;
		$this->link          = $this->tag->link;
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->params        = $this->state->get('params');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}


		$active = $app->getMenu()->getActive();

		// Check to see which parameters should take priority
		if ($active)
		{
			$currentLink = $active->link;
			if (strpos($currentLink, 'view=topics') && strpos($currentLink, '&id=' . (string) $this->tag->id)
				&& isset($active->query['layout']))
			{
				$this->setLayout($active->query['layout']);
			}


		}
		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx'));

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
		$tag      = $this->tag;
		$url      = rtrim(URI::root(), '/') . $tag->link;
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

		// If the menu item does not concern this contact
		if ($menu && ($menu->query['option'] !== 'com_discussions' || $menu->query['view'] !== 'topics' || $id != $tag->id))
		{
			$pathway->addItem($tag->title, '');

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
		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		// Set Meta Keywords
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// Set Meta Robots
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		// Set Meta Image
		if ($this->params->get('menu-meta_image', ''))
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