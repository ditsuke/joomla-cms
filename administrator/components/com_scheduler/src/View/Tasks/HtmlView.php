<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_scheduler
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Scheduler\Administrator\View\Tasks;

// Restrict direct access
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * MVC View for the Tasks list page.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * Array of task items.
	 *
	 * @var  array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $items;

	/**
	 * The pagination object.
	 *
	 * @var  Pagination
	 * @since  __DEPLOY_VERSION__
	 * @todo   Test pagination.
	 */
	protected $pagination;

	/**
	 * The model state.
	 *
	 * @var  CMSObject
	 * @since  __DEPLOY_VERSION__
	 */
	protected $state;

	/**
	 * A Form object for search filters.
	 *
	 * @var  Form
	 * @since  __DEPLOY_VERSION__
	 */
	public $filterForm;

	/**
	 * The active search filters.
	 *
	 * @var  array
	 * @since  __DEPLOY_VERSION__
	 */
	public $activeFilters;

	/**
	 * Is this view in an empty state?
	 *
	 * @var  boolean
	 * @since  __DEPLOY_VERSION__
	 */
	private $isEmptyState = false;

	/**
	 * @inheritDoc
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function display($tpl = null): void
	{
		$this->items         = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		if (!\count($this->items) && $this->isEmptyState = $this->get('IsEmptyState'))
		{
			$this->setLayout('empty_state');
		}

		// Check for errors.
		if (\count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
		}

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	protected function addToolbar(): void
	{
		$canDo = ContentHelper::getActions('com_scheduler');
		$user  = Factory::getApplication()->getIdentity();

		/*
		* Get the toolbar object instance
		* !! @todo : Replace usage with ToolbarFactoryInterface
		*/
		$toolbar = Toolbar::getInstance();

		ToolbarHelper::title(Text::_('COM_SCHEDULER_MANAGER_TASKS'), 'clock');

		if ($canDo->get('core.create'))
		{
			$toolbar->linkButton('new', 'JTOOLBAR_NEW')
				->url('index.php?option=com_scheduler&view=select&layout=default')
				->buttonClass('btn btn-success')
				->icon('icon-new');
		}

		if (!$this->isEmptyState && ($canDo->get('core.edit.state') || $user->authorise('core.admin')))
		{
			/** @var  DropdownButton $dropdown */
			$dropdown = $toolbar->dropdownButton('status-group')
				->toggleSplit(false)
				->text('JTOOLBAR_CHANGE_STATUS')
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			// Add the batch Enable, Disable and Trash buttons if privileged
			if ($canDo->get('core.edit.state'))
			{
				$childBar->addNew('tasks.publish', 'JTOOLBAR_ENABLE')->listCheck(true)->icon('icon-publish');
				$childBar->addNew('tasks.unpublish', 'JTOOLBAR_DISABLE')->listCheck(true)->icon('icon-unpublish');

				$childBar->checkin('tasks.unlock', 'COM_SCHEDULER_TOOLBAR_UNLOCK')->listCheck(true)->icon('icon-unlock');

				// We don't want the batch Trash button if displayed entries are all trashed
				if ($this->state->get('filter.state') != -2)
				{
					$childBar->trash('tasks.trash')->listCheck(true);
				}
			}
		}

		// Add "Empty Trash" button if filtering by trashed.
		if ($this->state->get('filter.state') == -2 && $canDo->get('core.delete'))
		{
			$toolbar->delete('tasks.delete')
				->message('JGLOBAL_CONFIRM_DELETE')
				->text('JTOOLBAR_EMPTY_TRASH')
				->listCheck(true);
		}

		// Link to component preferences if user has admin privileges
		if ($canDo->get('core.admin') || $canDo->get('core.options'))
		{
			$toolbar->preferences('com_scheduler');
		}

		$toolbar->help('JHELP_COMPONENTS_SCHEDULED_TASKS_MANAGER');
	}
}
