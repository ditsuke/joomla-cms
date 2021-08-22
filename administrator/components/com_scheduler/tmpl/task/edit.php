<?php
/**
 * @package       Joomla.Administrator
 * @subpackage    com_scheduler
 *
 * @copyright (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @codingStandardsIgnoreStart
 */

// Restrict direct access
defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Scheduler\Administrator\Task\TaskOption;
use Joomla\Component\Scheduler\Administrator\View\Task\HtmlView;

/** @var  HtmlView  $this */

$wa = $this->document->getWebAssetManager();

$wa->useScript('keepalive');
$wa->useScript('form.validate');
$wa->useStyle('com_scheduler.admin-view-task-css');

/** @var AdministratorApplication $app */
$app = $this->app;

$input = $app->getInput();

// ?
$this->ignore_fieldsets = [];

// ? : Are these of use here?
$isModal = $input->get('layout') === 'modal';
$layout = $isModal ? 'modal' : 'edit';
$tmpl = $isModal || $input->get('tmpl', '') === 'component' ? '&tmpl=component' : '';
?>

<!-- Form begins -->
<form action="<?php echo Route::_('index.php?option=com_scheduler&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>"
	  method="post" name="adminForm" id="task-form"
	  aria-label="<?php echo Text::_('COM_SCHEDULER_FORM_TITLE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>"
	  class="form-validate">

	<!-- The task title field -->
	<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>

	<!-- The main form card -->
	<div class="main-card">
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

		<!-- The first (and the main) tab in the form -->
		<?php echo
		HTMLHelper::_('uitab.addTab',
				'myTab', 'general',
				empty($this->item->id) ? Text::_('COM_SCHEDULER_NEW_TASK') : Text::_('COM_SCHEDULER_EDIT_TASK')
		);
		?>
		<div class="row">
			<div class="col-lg-9">
				<!-- Task type title, description go here -->
				<?php if ($this->item->taskOption):
					/** @var TaskOption $taskOption */
					$taskOption = $this->item->taskOption; ?>
					<div id="taskOptionInfo">
						<h2 id="taskOptionTitle">
							<?php echo $taskOption->title ?>
						</h2>
						<p id="taskOptionDesc">
							<?php
							// @todo: For long descriptions, we'll want a "read more" functionality like com_modules
							$desc = HTMLHelper::_('string.truncate', $this->escape(strip_tags($taskOption->desc)), 250);
							echo $desc;
							?>
						</p>
					</div>
					<!-- If TaskOption does not exist -->
				<?php else:
					$app->enqueueMessage(Text::_('COM_SCHEDULER_WARNING_EXISTING_TASK_TYPE_NOT_FOUND'), 'warning');
					?>
				<?php endif; ?>
				<fieldset class="options-form">
					<legend><?php echo Text::_('COM_SCHEDULER_FIELDSET_BASIC'); ?></legend>
					<?php echo $this->form->renderFieldset('basic'); ?>
				</fieldset>


				<fieldset class="options-form match-custom"
						  data-showon='[{"field":"jform[execution_rules][rule-type]","values":["custom"],"sign":"=","op":""}]'
				>
					<legend><?php echo Text::_('COM_SCHEDULER_FIELDSET_CRON_OPTIONS'); ?></legend>
					<?php echo $this->form->renderFieldset('custom-cron-rules'); ?>
				</fieldset>

				<fieldset class="options-form">
					<legend><?php echo Text::_('COM_SCHEDULER_FIELDSET_PARAMS_FS'); ?></legend>
					<?php
					// @todo: Render [all] fieldsets with the Joomla params template
					// ! Investigate why `render('joomla.edit.params', $this)` fails
					echo $this->form->renderFieldset('params-fs');
					?>
				</fieldset>
			</div>
			<div class="col-lg-3">
				<?php echo $this->form->renderFieldset('aside'); ?>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<!-- Tab to show execution history-->
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'exec_hist', Text::_('COM_SCHEDULER_FIELDSET_EXEC_HIST')); ?>
		<div class="row">
			<div class="col-lg-9">
				<fieldset class="options-form">
					<legend><?php echo Text::_('COM_SCHEDULER_FIELDSET_EXEC_HIST'); ?></legend>
					<?php echo $this->form->renderFieldset('exec_hist'); ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<!-- Tab to show creation details-->
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('JDETAILS')); ?>
		<div class="row">
			<div class="col-lg-9">
				<fieldset class="options-form">
					<legend><?php echo Text::_('JDETAILS'); ?></legend>
					<?php echo $this->form->renderFieldset('details'); ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>

		<!-- Item permissions tab, if user has admin privileges -->
		<?php if ($this->canDo->get('core.admin')) : ?>
			<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JCONFIG_PERMISSIONS_LABEL')); ?>
			<fieldset id="fieldset-permissions" class="options-form">
				<legend><?php echo Text::_('JCONFIG_PERMISSIONS_LABEL'); ?></legend>
				<div>
					<?php echo $this->form->getInput('rules'); ?>
				</div>
			</fieldset>
			<?php echo HTMLHelper::_('uitab.endTab'); ?>
		<?php endif; ?>
		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		<?php echo $this->form->getInput('context'); ?>
		<input type="hidden" name="task" value="">
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>