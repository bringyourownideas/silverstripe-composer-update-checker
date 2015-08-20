<?php
/**
 * Composer Update checker job
 *
 * @author Peter Thaleikis
 * @license MIT
 */
class CheckComposerUpdatesJob extends AbstractQueuedJob implements QueuedJob {
	/**
	 * The task to run
	 *
	 * @var BuildTask
	 */
	protected $task;

	/**
	 * define the title
	 *
	 * @return string
	 */
	public function getTitle() {
		return _t(
			'ComposerUpdateChecker.Title',
			'Check if composer updates are available'
		);
	}

	/**
	 * define the type.
	 */
	public function getJobType() {
		$this->totalSteps = 1;
		return QueuedJob::QUEUED;
	}

	/**
	 * init
	 */
	public function setup() {
		// create the instance of the task
		$this->task = new CheckComposerUpdatesTask();
	}

	/**
	 * process the
	 */
	public function process() {
		$this->task->process(new SS_HTTPRequest());
	}
}
