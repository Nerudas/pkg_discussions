<?php
/**
 * @package    Discussions Component
 * @version    1.0.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::_('formbehavior.chosen', 'select');

$filters = array_keys($this->filterForm->getGroup('filter'));
?>
<?php echo '<pre>', print_r($this->tag, true), '</pre>'; ?>
<form action="<?php echo htmlspecialchars(Factory::getURI()->toString()); ?>" method="get" name="adminForm">
	<?php foreach ($filters as $filter): ?>
		<?php echo $this->filterForm->renderField(str_replace('filter_', '', $filter), 'filter'); ?>
	<?php endforeach; ?>
	<button type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
	<a href="<?php echo $this->tag->link; ?>"><?php echo Text::_('JCLEAR'); ?></a>
</form>
<?php if (!empty($this->items)): ?>
	<?php foreach ($this->items as $item): ?>
		<div>
			<div>
				<a href="<?php echo $item->link; ?>"><?php echo $item->title; ?></a>
			</div>
			<?php echo '<pre>', print_r($item, true), '</pre>'; ?>
		</div>

	<?php endforeach; ?>
<?php endif; ?>


<?php echo $this->pagination->getListFooter(); ?>
