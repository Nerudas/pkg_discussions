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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$user    = Factory::getUser();
$columns = 5;
$canDo   = DiscussionsHelper::getActions('com_discussions');

?>

<table class="table table-striped">
	<thead>
	<tr>
		<th style="min-width:100px" class="nowrap">
			<?php echo Text::_('JGLOBAL_TITLE'); ?>
		</th>
		<th width="10%" class="nowrap hidden-phone">
			<?php echo Text::_('JAUTHOR'); ?>
		</th>
		<th width="10%" class="nowrap hidden-phone">
			<?php echo Text::_('JGRID_HEADING_REGION'); ?>
		</th>
		<th width="10%" class="nowrap hidden-phone">
			<?php echo Text::_('JGLOBAL_CREATED_DATE'); ?>
		</th>
		<th width="1%" class="nowrap hidden-phone">
			<?php echo Text::_('JGLOBAL_HITS'); ?>
		</th>
		<th width="1%" class="nowrap hidden-phone center">
			<?php echo Text::_('JGRID_HEADING_ID'); ?>
		</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="<?php echo $columns; ?>">
		</td>
	</tr>
	</tfoot>
	<tbody>
	<?php foreach ($items as $i => $item): ?>
		<tr item-id="<?php echo $item->id ?>">
			<td>
				<div>
					<?php if ($canDo->get('core.edit') && empty($item->context) && empty($item->item_id)): ?>
						<a class="hasTooltip" title="<?php echo Text::_('JACTION_EDIT'); ?>"
						   href="<?php echo Route::_('index.php?option=com_discussions&task=topic.edit&id=' . $item->id); ?>">
							<?php echo $item->title; ?>
						</a>
					<?php else : ?>
						<?php echo $item->title; ?>
					<?php endif; ?>
				</div>
				<div class="tags">
					<?php if (!empty($item->tags->itemTags)): ?>
						<?php foreach ($item->tags->itemTags as $tag): ?>
							<span class="label label-inverse">
								<?php echo $tag->title; ?>
							</span>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</td>
			<td class="hidden-phone">
				<?php if ((int) $item->created_by != 0): ?>
					<div class="author">
						<div class="avatar<?php echo ($item->author_online) ? ' online' : '' ?>"
							 style="background-image: url('<?php echo $item->author_avatar; ?>')">
						</div>
						<div>
							<div class="name">
								<a class="hasTooltip nowrap" title="<?php echo Text::_('JACTION_EDIT'); ?>"
								   target="_blank"
								   href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id='
									   . (int) $item->author_id); ?>">
									<?php echo $item->author_name; ?>
								</a>
							</div>
							<?php if ($item->author_job): ?>
								<div class="job">
									<a class="hasTooltip nowrap"
									   title="<?php echo Text::_('JACTION_EDIT'); ?>"
									   target="_blank"
									   href="<?php echo Route::_('index.php?option=com_companies&task=company.edit&id='
										   . $item->author_job_id); ?>">
										<?php echo $item->author_job_name; ?>
									</a>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</td>
			<td class="small hidden-phone nowrap">
				<?php echo ($item->region !== '*') ? $item->region_name :
					Text::_('JGLOBAL_FIELD_REGIONS_ALL'); ?>
			</td>
			<td class="nowrap small hidden-phone">
				<?php echo $item->created > 0 ? HTMLHelper::_('date', $item->created,
					Text::_('DATE_FORMAT_LC2')) : '-' ?>
			</td>
			<td class="hidden-phone center">
				<span class="badge badge-info">
					<?php echo (int) $item->hits; ?>
				</span>
			</td>
			<td class="hidden-phone center">
				<?php echo $item->id; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
