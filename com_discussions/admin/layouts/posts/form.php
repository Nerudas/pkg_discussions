<?php
/**
 * @package    Discussions Component
 * @version    1.0.4
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var  Form   $form   Form object
 * @var  string $action Action link;
 */

?>
<?php if ($form): ?>
	<form action="<?php echo $action; ?>" method="post">
		<?php
		echo $form->renderField('text');
		echo $form->renderField('captcha');
		echo $form->renderFieldSet('hidden'); ?>
		<input type="hidden" name="task" value="post.save">
		<input type="hidden" name="return" value="<?php echo base64_encode(Factory::getUri()->toString()); ?>">
		<?php echo HTMLHelper::_('form.token'); ?>
		<button><?php echo Text::_('JAPPLY'); ?></button>
	</form>
<?php endif; ?>