<?php

namespace BringYourOwnIdeas\UpdateChecker\Jobs;

use SilverStripe\Dev\BuildTask;
use Symbiote\QueuedJobs\Services\QueuedJob;
use SilverStripe\Core\Injector\Injector;
use BringYourOwnIdeas\UpdateChecker\Tasks\CheckComposerUpdatesTask;
use SilverStripe\Control\HTTPRequest;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;

/**
 * Composer update checker job. Runs the check as a queuedjob.
 *
 * @author Peter Thaleikis
 * @license MIT
 */
class CheckComposerUpdatesJob extends AbstractQueuedJob implements QueuedJob
{
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
    public function getTitle()
    {
        return _t(
            'ComposerUpdateChecker.Title',
            'Check if composer updates are available'
        );
    }

    /**
     * define the type.
     */
    public function getJobType()
    {
        $this->totalSteps = 1;

        return QueuedJob::QUEUED;
    }

    /**
     * init
     */
    public function setup()
    {
        // create the instance of the task
        $this->task = Injector::inst()->create(CheckComposerUpdatesTask::class);
    }

    /**
     * processes the task as a job
     */
    public function process()
    {
        // run the task
        $this->task->run(new HTTPRequest('GET', '/'));

        // mark job as completed
        $this->isComplete = true;
    }
}
