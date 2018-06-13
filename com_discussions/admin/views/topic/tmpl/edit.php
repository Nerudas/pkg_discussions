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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$app = Factory::getApplication();
$doc = Factory::getDocument();

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::stylesheet('media/com_discussions/css/admin-topic.min.css', array('version' => 'auto'));

$doc->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "topic.cancel" || task == "item.setContacts" || document.formvalidator.isValid(document.getElementById("item-form")))
		{
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	};
');
?>
<form action="<?php echo Route::_('index.php?option=com_discussions&view=topic&id=' . $this->item->id); ?>"
	  method="post"
	  name="adminForm" id="item-form" class="form-validate" enctype="multipart/form-data">

	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('JGLOBAL_FIELDSET_CONTENT')); ?>
		<div class="row-fluid">
			<div class="span9">
				<fieldset class="adminform">
					<?php echo $this->form->getInput('text'); ?>
				</fieldset>
			</div>
			<div class="span3">
				<fieldset class="form-vertical">
					<?php echo $this->form->renderFieldset('global'); ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php
		echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'categories', Text::_('COM_DISCUSSIONS_TOPIC_CATEGORIES'));
		echo LayoutHelper::render('components.com_discussions.form.categories', $this);
		echo HTMLHelper::_('bootstrap.endTab');
		?>

		<?php
		echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'images', Text::_('COM_DISCUSSIONS_TOPIC_IMAGES'));
		echo $this->form->getInput('images');
		echo HTMLHelper::_('bootstrap.endTab');
		?>



		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING')); ?>
		<div class="row-fluid form-horizontal-desktop">
			<div class="span6">
				<?php echo $this->form->renderFieldset('publishingdata'); ?>
			</div>
			<div class="span6">
				<?php echo $this->form->renderFieldset('metadata'); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php
		echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'attribs', Text::_('JGLOBAL_FIELDSET_OPTIONS'));
		echo $this->form->renderFieldset('attribs');
		echo HTMLHelper::_('bootstrap.endTab');
		?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="return" value="<?php echo $app->input->getCmd('return'); ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>