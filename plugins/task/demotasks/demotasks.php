<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  Task.DemoTasks
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Restrict direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;

/**
 * A demo task plugin. Offers 3 task routines and demonstrates the use of {@see TaskPluginTrait},
 * {@see ExecuteTaskEvent}.
 *
 * @since __DEPLOY__VERSION__
 */
class PlgTaskDemotasks extends CMSPlugin implements SubscriberInterface
{
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since __DEPLOY_VERSION__
	 */
	private const TASKS_MAP = [
		'demoTask_r1.sleep'                    => [
			'langConstPrefix' => 'PLG_TASK_DEMO_TASKS_TASK_SLEEP',
			'method'          => 'sleep',
			'form'            => 'testTaskForm',
		],
		'demoTask_r2.memoryStressTest'         => [
			'langConstPrefix' => 'PLG_TASK_DEMO_TASKS_STRESS_MEMORY',
			'method'          => 'stressMemory',
		],
		'demoTask_r3.memoryStressTestOverride' => [
			'langConstPrefix' => 'PLG_TASK_DEMO_TASKS_STRESS_MEMORY_OVERRIDE',
			'method'          => 'stressMemoryRemoveLimit',
		],
	];

	/**
	 * @var boolean
	 * @since __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @since __DEPLOY_VERSION__
	 * @throws Exception
	 */
	private function sleep(ExecuteTaskEvent $event): int
	{
		$timeout = (int) $event->getArgument('params')->timeout ?? 1;

		$this->logTask(sprintf('Starting %d timeout', $timeout));
		sleep($timeout);
		$this->logTask(sprintf('%d timeout over!', $timeout));

		return Status::OK;
	}

	/**
	 * Standard routine method for the memory test routine.
	 *
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @since __DEPLOY_VERSION__
	 * @throws Exception
	 */
	private function stressMemory(ExecuteTaskEvent $event): int
	{
		$mLimit = $this->getMemoryLimit();
		$this->logTask(sprintf('Memory Limit: %d KB', $mLimit));

		$iMem = $cMem = memory_get_usage();
		$i    = 0;

		while ($cMem + ($cMem - $iMem) / ++$i <= $mLimit)
		{
			$this->logTask(sprintf('Current memory usage: %d KB', $cMem));
			${"array" . $i} = array_fill(0, 100000, 1);
		}

		return Status::OK;
	}

	/**
	 * Standard routine method for the memory test routine, also attempts to override the memory limit set by the PHP
	 * INI.
	 *
	 * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
	 *
	 * @return integer  The routine exit code.
	 *
	 * @since __DEPLOY_VERSION__
	 * @throws Exception
	 */
	private function stressMemoryRemoveLimit(ExecuteTaskEvent $event): int
	{
		$success = false;

		if (function_exists('ini_set'))
		{
			$success = ini_set('memory_limit', -1) !== false;
		}

		$this->logTask('Memory limit override ' . $success ? 'successful' : 'failed');

		return $this->stressMemory($event);
	}

	/**
	 * Processes the PHP ini memory_limit setting, returning the memory limit in KB
	 *
	 * @return float
	 *
	 * @since __DEPLOY_VERSION__
	 */
	private function getMemoryLimit(): float
	{
		$memoryLimit = ini_get('memory_limit');

		if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches))
		{
			if ($matches[2] == 'M')
			{
				// * nnnM -> nnn MB
				$memoryLimit = $matches[1] * 1024 * 1024;
			}
			else
			{
				if ($matches[2] == 'K')
				{
					// * nnnK -> nnn KB
					$memoryLimit = $matches[1] * 1024;
				}
			}
		}

		return (float) $memoryLimit;
	}
}
