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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

?>

<h1><?php echo Text::_('COM_DISCUSSIONS_TOPIC'); ?></h1>
<?php //echo '<pre>', print_r($this->topic, true), '</pre>'; ?>
<?php
$data               = array();
$data['topic_id']   = $this->topic->id;
$data['items']      = $this->items;
$data['total']      = $this->total;
$data['pagination'] = $this->pagination;
$data['addForm']    = $this->addPostForm;
echo LayoutHelper::render('components.com_discussions.posts.list', $data); ?>
