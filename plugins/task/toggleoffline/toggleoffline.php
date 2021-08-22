<?php
/**
 * A task plugin to toggle the offline status of the site.
 *
 * @package       Joomla.Plugins
 * @subpackage    Task.ToggleOffline
 *
 * @copyright (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GNU General Public License version 2 or later; see LICENSE.txt
 */

// Restrict direct access
defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * The plugin class
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgTaskToggleoffline extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since __DEPLOY_VERSION__
	 */
	protected const TASKS_MAP = [
		'plg_task_toggle_offline' => [
			'langConstPrefix' => 'PLG_TASK_TOGGLE_OFFLINE',
			'toggle' => true
		],
		'plg_task_toggle_offline_set_online' => [
			'langConstPrefix' => 'PLG_TASK_TOGGLE_OFFLINE_SET_ONLINE',
			'toggle' => false,
			'offline' => false
		],
		'plg_task_toggle_offline_set_offline' => [
			'langConstPrefix' => 'PLG_TASK_TOGGLE_OFFLINE_SET_OFFLINE',
			'toggle' => false,
			'offline' => true
		],

	];

	/**
	 * The application object
	 *
	 * @var  CMSApplication
	 * @since __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Autoload the language file
	 *
	 * @var boolean
	 * @since __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * An array of supported Form contexts
	 *
	 * @var string[]
	 * @since __DEPLOY_VERSION__
	 */
	private $supportedFormContexts = [
		'com_scheduler.task'
	];

	/**
	 * Returns event subscriptions
	 *
	 * @return string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList' => 'advertiseRoutines',
			'onExecuteTask' => 'toggleOffline',
		];
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The onExecuteTask event
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since __DEPLOY_VERSION__
	 */
	public function toggleOffline(ExecuteTaskEvent $event): void
	{
		if (!array_key_exists($event->getTaskId(), self::TASKS_MAP))
		{
			return;
		}

		$this->taskStart();

		$config = ArrayHelper::fromObject(new JConfig);

		$toggle = self::TASKS_MAP[$event->getTaskId()]['toggle'];
		$oldStatus = $config['offline'] ? 'offline' : 'online';

		if ($toggle)
		{
			$config['offline'] = !$config['offline'];
		}
		else
		{
			$offline = self::TASKS_MAP[$event->getTaskId()]['offline'];
			$config['offline'] = $offline;
		}

		$newStatus = $config['offline'] ? 'offline' : 'online';
		$exit = $this->writeConfigFile(new Registry($config));
		$this->addTaskLog(Text::sprintf('PLG_TASK_TOGGLE_OFFLINE_TASK_LOG_SITE_STATUS', $oldStatus, $newStatus));

		$this->taskEnd($event, $exit);
	}

	/**
	 * Method to write the configuration to a file.
	 *
	 * @param   Registry  $config  A Registry object containing all global config data.
	 *
	 * @return  integer  The task exit code
	 *
	 * @throws Exception
	 * @since  __DEPLOY_VERSION__
	 */
	private function writeConfigFile(Registry $config): int
	{
		// Set the configuration file path.
		$file = JPATH_CONFIGURATION . '/configuration.php';

		// Attempt to make the file writeable.
		if (Path::isOwner($file) && !Path::setPermissions($file))
		{
			$this->addTaskLog(Text::_('PLG_TASK_TOGGLE_OFFLINE_ERROR_CONFIGURATION_PHP_NOTWRITABLE'), 'notice');
		}

		// Attempt to write the configuration file as a PHP class named JConfig.
		$configuration = $config->toString('PHP', array('class' => 'JConfig', 'closingtag' => false));

		if (!File::write($file, $configuration))
		{
			$this->addTaskLog(Text::_('PLG_TASK_TOGGLE_OFFLINE_ERROR_WRITE_FAILED'), 'error');

			return self::$STATUS['KO_RUN'];
		}

		// Invalidates the cached configuration file
		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($file);
		}

		// Attempt to make the file un-writeable.
		if (Path::isOwner($file) && !Path::setPermissions($file, '0444'))
		{
			$this->addTaskLog(Text::_('PLG_TASK_TOGGLE_OFFLINE_ERROR_CONFIGURATION_PHP_NOTUNWRITABLE'), 'notice');
		}

		return self::$STATUS['OK_RUN'];
	}
}
