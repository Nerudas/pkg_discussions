<?php
/**
 * @package    Discussions - Administrator Module
 * @version    1.0.6
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('script', 'media/mod_discussions_admin/js/ajax.min.js', array('version' => 'auto'));
HTMLHelper::_('stylesheet', 'media/mod_discussions_admin/css/default.min.css', array('version' => 'auto'));
?>

<div data-mod-discussions-admin="<?php echo $module->id; ?>">
	<?php echo HTMLHelper::_('bootstrap.startTabSet', 'modDiscussionsAdmin' . $module->id, array('active' => 'posts')); ?>

	<?php echo HTMLHelper::_('bootstrap.addTab', 'modDiscussionsAdmin' . $module->id, 'posts',
		Text::_('COM_DISCUSSIONS_POSTS')); ?>
	<div data-mod-discussions-admin-tab="posts">
		<div class="loading">
			<?php echo Text::_('MOD_DISCUSSIONS_ADMIN_LOADING'); ?>
		</div>
		<div class="result">
			<div class="items"></div>
		</div>
	</div>
	<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

	<?php echo HTMLHelper::_('bootstrap.addTab', 'modDiscussionsAdmin' . $module->id, 'topics',
		Text::_('COM_DISCUSSIONS_TOPICS')); ?>
	<div data-mod-discussions-admin-tab="topics">
		<div class="loading">
			<?php echo Text::_('MOD_DISCUSSIONS_ADMIN_LOADING'); ?>
		</div>
		<div class="result">
			<div class="items"></div>
		</div>
	</div>
	<?php echo HTMLHelper::_('bootstrap.endTab'); ?>


	<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
	<div class="actions ">
		<div class="btn-group">
			<a class="btn"
			   href="<?php echo Route::_('index.php?option=com_discussions'); ?>">
				<?php echo Text::_('MOD_DISCUSSIONS_ADMIN_TO_COMPONENT'); ?>
			</a>
			<a class="btn"
			   data-mod-discussions-admin-reload="<?php echo $module->id; ?>"
			   title="<?php echo Text::_('MOD_DISCUSSIONS_ADMIN_REFRESH'); ?>">
				<i class="icon-loop"></i>
			</a>
		</div>
	</div>
</div>

