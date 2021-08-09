<?php
/**
 * Declares the CronjobsPluginTrait.
 *
 * @package       Joomla.Administrator
 * @subpackage    com_cronjobs
 *
 * @copyright (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license       GPL v3
 */

namespace Joomla\Component\Cronjobs\Administrator\Traits;

// Restrict direct access
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Cronjobs\Administrator\Event\CronRunEvent;

/**
 * Utility trait for plugins that support com_cronjobs jobs
 *
 * @since __DEPLOY_VERSION__
 */
trait CronjobPluginTrait
{
	/**
	 * Stores the job state.
	 *
	 * @var array
	 * @since __DEPLOY_VERSION__
	 */
	protected $snapshot = [];

	/**
	 * Predefined exit codes
	 *
	 * @var string[]
	 * @since  __DEPLOY_VERSION__
	 */
	private static $STATUS = [
		'NO_TIME' => 1,
		'NO_RUN' => 3,
		'OK_RUN' => 0
	];

	/**
	 * Sets boilerplate to snapshot when starting a job
	 *
	 * @return void
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function jobStart(): void
	{
		if (!$this instanceof CMSPlugin)
		{
			return;
		}

		$this->snapshot['plugin'] = $this->_name;
		$this->snapshot['startTime'] = microtime(true);
		$this->snapshot['status'] = self::$STATUS['NO_TIME'];
	}

	/**
	 * Sets exit code and duration to snapshot. Writes to log.
	 *
	 * @param   CronRunEvent  $event     The event
	 * @param   ?int          $exitCode  The job exit code
	 * @param   boolean       $log       If true, the method adds a log. Requires the plugin to
	 *                                   have the language strings.
	 *
	 * @return void
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function jobEnd(CronRunEvent $event, int $exitCode, bool $log = true): void
	{
		if (!$this instanceof CMSPlugin)
		{
			return;
		}

		$this->snapshot['endTime'] = $endTime = microtime(true);
		$this->snapshot['duration'] = $endTime - $this->snapshot['startTime'];
		$this->snapshot['status'] = $exitCode ?? self::$STATUS['OK_RUN'];
		$event->setResult($this->snapshot);

		if ($log)
		{
			$jobNsSuffix = '_' . strtoupper($event->getArgument('langNsSuffix')) . '_';
			Log::add(
				Text::sprintf('PLG_JOB_' . strtoupper($this->_name) . $jobNsSuffix . 'JOB_LOG_MESSAGE',
					$this->snapshot['status'], $this->snapshot['duration']
				),
				Log::INFO,
				'cronjobs'
			);
		}
	}
}
