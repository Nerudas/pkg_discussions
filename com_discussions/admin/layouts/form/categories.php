<?php
/**
 * @package    Discussions Component
 * @version    1.0.3
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;


use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('jquery.framework');
HTMLHelper::_('stylesheet', 'media/com_discussions/css/form-categories.min.css', array('version' => 'auto'));
HTMLHelper::_('script', 'media/com_discussions/js/form-categories.min.js', array('version' => 'auto'));

$form       = $displayData->getForm();
$categories = $displayData->getCategories();
$model      = $displayData->getModel();


$children = array();
$actives  = array();
$root     = array();

$i = 0;
foreach ($categories as $category)
{
	if ($i == 0)
	{
		$first = $category->id;
	}

	if ($category->active)
	{
		$actives[$category->id] = $category->title;
	}

	if ($category->level == 1)
	{
		$root[$category->id] = $category;
	}

	if (!isset($children[$category->parent_id]))
	{
		$children[$category->parent_id] = array();
	}
	$children[$category->parent_id][$category->id] = $category;

	$i++;
}
if (empty($first))
{
	$first = 'extd';
}


?>
<div data-input-discussionscategories="">
	<div class="currents">
		<ul class="actives">
			<?php foreach ($actives as $active): ?>
				<li class="item">
					<?php echo $active; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<div id="categorySelect" class="">
		<div class="tabbable tabs-left">
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'categories', array('active' => $first, 'class' => ' tabs-left')); ?>
			<?php foreach ($root as $item): ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'categories', $item->id, $item->title); ?>
				<div class="categories">
					<?php if (!empty($children[$item->id])): ?>
						<?php foreach ($children[$item->id] as $child) : ?>
							<a class="item <?php if ($child->active) echo 'active'; ?>"
							   data-tags="[<?php echo implode(',', $child->tags); ?>]"
							   data-title="<?php echo $child->title; ?>">
								<?php if (!empty($child->icon))
								{
									echo HTMLHelper::_('image', $child->icon, $child->title, array('title' => $child->title));
								}
								?>
								<span class="title"><?php echo $child->title; ?></span>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php endforeach; ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'categories', 'extd', Text::_('COM_DISCUSSIONS_TOPIC_CATEGORIES_EXTENDED')); ?>
			<?php echo $form->getInput('tags'); ?>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

			<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		</div>
	</div>
</div>