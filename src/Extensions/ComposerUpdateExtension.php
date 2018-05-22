<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

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
        'VersionConstraint' => 'Varchar(50)',
        'AvailableVersion' => 'Varchar(50)',
        'AvailableHash' => 'Varchar(50)',
        'LatestVersion' => 'Varchar(50)',
        'LatestHash' => 'Varchar(50)',
    ];

    /**
     * Automatically schedule a self update job on dev/build
     */
    public function requireDefaultRecords()
    {
        Injector::inst()->get(QueuedJobService::class)->queueJob($this->getJobName());
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
