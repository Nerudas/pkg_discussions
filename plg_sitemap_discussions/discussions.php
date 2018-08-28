<?php
/**
 * @package    Sitemap - Discussions Plugin
 * @version    1.1.1
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class plgSitemapDiscussions extends CMSPlugin
{

	/**
	 * Urls array
	 *
	 * @var    array
	 *
	 * @since  1.0.0
	 */
	protected $_urls = null;

	/**
	 * Method to get Links array
	 *
	 * @return array
	 *
	 * @since 1.1.1
	 */
	public function getUrls()
	{
		if ($this->_urls === null)
		{

			// Include route helper
			JLoader::register('DiscussionsHelperRoute', JPATH_SITE . '/components/com_discussions/helpers/route.php');

			$db   = Factory::getDbo();
			$user = Factory::getUser(0);

			// Get items
			$query = $db->getQuery(true)
				->select('t.id')
				->from($db->quoteName('#__discussions_topics', 't'))
				->where('t.state = 1')
				->where('t.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');

			// Join over last post.
			$lastPostQuery = $db->getQuery(true)
				->select('sub_post.id')
				->from('#__discussions_posts as sub_post')
				->where('sub_post.topic_id = t.id')
				->where('sub_post.access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')')
				->where('sub_post.state = 1')
				->order('sub_post.created DESC')
				->setLimit(1);

			$query->select('(CASE WHEN last_post.id IS NOT NULL THEN last_post.created ELSE t.created END) as modified')
				->join('LEFT', '#__discussions_posts AS last_post ON last_post.id = (' . (string) $lastPostQuery . ')');

			$query->group(array('t.id'))
				->order('modified DESC');

			$db->setQuery($query);
			$topics = $db->loadObjectList('id');

			$topic_changefreq = $this->params->def('topic_changefreq', 'weekly');
			$topic_priority   = $this->params->def('topic_priority', '0.5');


			foreach ($topics as $topic)
			{
				$url             = new stdClass();
				$url->loc        = DiscussionsHelperRoute::getTopicRoute($topic->id);
				$url->changefreq = $topic_changefreq;
				$url->priority   = $topic_priority;
				$url->lastmod    = $topic->modified;

				$topics_urls[] = $url;
			}

			// Get Tags
			$navtags        = ComponentHelper::getParams('com_discussions')->get('tags', array());
			$tag_changefreq = $this->params->def('tag_changefreq', 'weekly');
			$tag_priority   = $this->params->def('tag_priority', '0.5');

			$tags              = array();
			$tags[1]           = new stdClass();
			$tags[1]->id       = 1;
			$tags[1]->modified = array_shift($topics)->modified;

			if (!empty($navtags))
			{
				$query = $db->getQuery(true)
					->select(array('tm.tag_id as id', 'max(tm.tag_date) as modified'))
					->from($db->quoteName('#__contentitem_tag_map', 'tm'))
					->join('LEFT', '#__tags AS t ON t.id = tm.tag_id')
					->where($db->quoteName('tm.type_alias') . ' = ' . $db->quote('com_discussions.topic'))
					->where('tm.tag_id IN (' . implode(',', $navtags) . ')')
					->where('t.published = 1')
					->where('t.access IN (' . implode(',', Factory::getUser(0)->getAuthorisedViewLevels()) . ')')
					->group('t.id');
				$db->setQuery($query);

				$tags = $tags + $db->loadObjectList('id');
			}

			$tags_urls = array();
			foreach ($tags as $tag)
			{
				$url             = new stdClass();
				$url->loc        = DiscussionsHelperRoute::getTopicsRoute($tag->id);
				$url->changefreq = $tag_changefreq;
				$url->priority   = $tag_priority;
				$url->lastmod    = $tag->modified;

				$tags_urls[] = $url;
			}

			$this->_urls = array_merge($tags_urls, $topics_urls);
		}

		return $this->_urls;

	}
}