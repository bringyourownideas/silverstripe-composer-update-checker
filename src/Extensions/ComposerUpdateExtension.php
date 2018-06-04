<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use BringYourOwnIdeas\UpdateChecker\Jobs\CheckComposerUpdatesJob;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\ORM\DataExtension;

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
    protected $jobName = CheckComposerUpdatesJob::class;

    private static $db = [
        'VersionHash' => 'Varchar',
        'VersionConstraint' => 'Varchar(50)',
        'AvailableVersion' => 'Varchar(50)',
        'AvailableHash' => 'Varchar(50)',
        'LatestVersion' => 'Varchar(50)',
        'LatestHash' => 'Varchar(50)',
    ];

    private static $summary_fields = [
        'AvailableVersion' => 'Available',
        'LatestVersion' => 'Latest',
    ];

    /**
     * Automatically schedule a self update job on dev/build
     */
    public function requireDefaultRecords()
    {
        $job = Injector::inst()->create($this->getJobName());
        Injector::inst()->get(QueuedJobService::class)->queueJob($job);
    }

    /**
     * If the available version is the same as the current version then return nothing, otherwise show the latest
     * available version
     *
     * @return string
     */
    public function getAvailableVersion()
    {
        if ($this->owner->getField('Version') === $this->owner->getField('AvailableVersion')) {
            return '';
        }
        return $this->owner->getField('AvailableVersion');
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
