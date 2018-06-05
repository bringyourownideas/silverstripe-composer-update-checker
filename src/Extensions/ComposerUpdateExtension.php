<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Describes any available updates to an installed Composer package
 *
 * Originally from https://github.com/XploreNet/silverstripe-composerupdates
 */
class ComposerUpdateExtension extends DataExtension
{
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
}
