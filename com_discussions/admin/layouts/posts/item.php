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

use Joomla\CMS\Layout\LayoutHelper;

$item = $displayData;
?>

<div>
	<div><?php echo $item->id; ?></div>
	<div><?php echo $item->text; ?></div>
	<?php //echo '<pre>', print_r($item, true), '</pre>'; ?>
	<div>
		<?php
		$data           = array();
		$data['form']   = $item->form;
		$data['action'] = $item->editLink;
		echo LayoutHelper::render('components.com_discussions.posts.form', $data); ?>
	</div>
</div>
<hr>
