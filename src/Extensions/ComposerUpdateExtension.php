<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use CheckComposerUpdatesJob;
use DataExtension;
use Injector;
use QueuedJobService;

/**
 * Describes any available updates to an installed Composer package
 *
 * Originally from https://github.com/XploreNet/silverstripe-composerupdates
 */
class ComposerUpdateExtension extends DataExtension
{
    /**
     * @var string
     */
    protected $jobName = 'CheckComposerUpdatesJob';

    private static $db = [
        'VersionHash' => 'Varchar',
        'LatestVersion' => 'Varchar',
    ];

    private static $summary_fields = [
        'LatestVersion',
    ];

    /**
     * Automatically schedule a self update job on dev/build
     */
    public function requireDefaultRecords()
    {
        Injector::inst()
            ->get(QueuedJobService::class)
            ->queueJob(new CheckComposerUpdatesJob());
    }

    /**
     * Return the name of the related job
     *
     * @return string
     */
    public function getJobName()
    {
        return $this->jobName;
    }
}
