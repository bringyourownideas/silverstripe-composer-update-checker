<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use Extension;

class ComposerUpdateReportExtension extends Extension
{
    /**
     * Adds the available and latest versions to the site summary report
     *
     * @param array $columns
     */
    public function updateColumns(&$columns)
    {
        $columns['AvailableVersion'] = [
            'title' => _t(__CLASS__ . '.AVAILABLE', 'Available'),
            // If the available version update is the same as the currently installed version, don't show it
            'formatting' => function ($value, $item) {
                if ($value === $item->Version) {
                    return '';
                }
                return $value;
            },
        ];

        $columns['LatestVersion'] = [
            'title' => _t(__CLASS__ . '.LATEST', 'Latest')
        ];
    }
}
