<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use Extension;

class ComposerUpdateReportExtension extends Extension
{
    /**
     * Adds the available and latest versions to the site summary report
     *
     * @param string[] $columns
     */
    public function updateColumns(&$columns)
    {
        $columns['AvailableVersion'] = 'Available';
        $columns['LatestVersion'] = 'Latest';
    }
}
