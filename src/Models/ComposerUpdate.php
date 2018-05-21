<?php
/**
 * Describes an available update to an installed Composer package
 *
 * Originally from https://github.com/XploreNet/silverstripe-composerupdates
 *
 * @author Matt Dwen
 * @license MIT
 */
class ComposerUpdate extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Name' => 'Varchar(255)',
        'Installed' => 'Varchar(255)',
        'Available' => 'Varchar(255)',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Name' => 'Package',
        'Installed' => 'Installed',
        'Available' => 'Available',
    );

    /**
     * name of the related job
     *
     * @var string
     */
    public $jobName = 'CheckComposerUpdatesJob';

    /**
     * self update on dev/build
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // add a queuedjob on dev/build
        singleton('QueuedJobService')->queueJob(new CheckComposerUpdatesJob());
    }
}
