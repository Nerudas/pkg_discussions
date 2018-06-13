<?php
/**
 * @package    Discussions Component
 * @version    1.0.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$children = array();
$actives  = array();
$root     = array();

foreach ($this->categories as $category)
{
	if ($category->level == 1)
	{
		$root[$category->id] = $category;
	}

	if (!isset($children[$category->parent_id]))
	{
		$children[$category->parent_id] = array();
	}
	$children[$category->parent_id][$category->id] = $category;
}
?>

<form action="<?php echo Route::_(DiscussionsHelperRoute::getTopicFormRoute()); ?>"
	  method="get">
	<ul>
		<?php foreach ($root as $item): ?>
			<li>
				<div>
					<label for="category_<?php echo $item->id; ?>">
						<input id="category_<?php echo $item->id; ?>" type="radio" name="category"
							   value="<?php echo $item->id; ?>" onchange="this.form.submit();" style="display: none;">
						<?php echo $item->title; ?>
					</label>
				</div>
				<?php if (!empty($children[$item->id])): ?>
					<ul>
						<?php foreach ($children[$item->id] as $child) : ?>
							<li>
								<div>
									<label for="category_<?php echo $child->id; ?>">
										<input id="category_<?php echo $child->id; ?>" type="radio" name="category"
											   value="<?php echo $child->id; ?>" onchange="this.form.submit();"
											   style="display: none;">
										<?php echo $child->title; ?>
									</label>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</form>