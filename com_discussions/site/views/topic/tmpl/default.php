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



?>


<?php if (!empty($this->items)): ?>
	<?php foreach ($this->items as $item): ?>
		<div>
			<div>
				<?php echo $item->id; ?>
			</div>
			<?php// echo '<pre>', print_r($item, true), '</pre>'; ?>
		</div>

	<?php endforeach; ?>
<?php endif; ?>

<?php echo $this->pagination->getListFooter(); ?>

