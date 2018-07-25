<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

/**
 * Task which does the actual checking of updates
 *
 * Originally from https://github.com/XploreNet/silverstripe-composerupdates
 *
 * @author Matt Dwen
 * @license MIT
 */
class CheckComposerUpdatesExtension extends Extension
{
    private static $dependencies = [
        'UpdateChecker' => '%$BringYourOwnIdeas\\UpdateChecker\\UpdateChecker',
    ];

    /**
     * @var UpdateChecker
     */
    protected $updateChecker;

    /**
     * Runs the actual steps to verify if there are updates available
     *
     * @param array[] $installedPackageList
     */
    public function updatePackageInfo(array &$installedPackageList)
    {
        // Fetch types of packages that are "allowed" - ie. dependencies that we actually care about
        $allowedTypes = (array) Config::inst()->get(UpdatePackageInfoTask::class, 'allowed_types');
        $composerPackagesAndConstraints = $this->owner->getComposerLoader()->getPackages($allowedTypes);

        // Loop list of packages given by owner task
        foreach ($installedPackageList as &$installedPackage) {
            /** @var array $installedPackage */
            if (empty($installedPackage['Name'])) {
                continue;
            }
            $packageName = $installedPackage['Name'];

            // Continue if we have no composer constraint details
            if (!isset($composerPackagesAndConstraints[$packageName])) {
                continue;
            }
            $packageData = $composerPackagesAndConstraints[$packageName];

            try {
                // Check for a relevant update version to recommend returned as keyed array and add to existing package
                // details array
                $updates = $this->getUpdateChecker()
                    ->checkForUpdates($packageData['package'], $packageData['constraint']);
            } catch (RuntimeException $ex) {
                // If exceptions are thrown during execution, fail gracefully and allow the rest of the report
                // generation to continue
                $updates = [];
                Injector::inst()->get(LoggerInterface::class)->debug($ex->getMessage());
            }

            $installedPackage = array_merge($installedPackage, $updates);
        }
    }

    /**
     * @param UpdateChecker $updateChecker
     * @return $this
     */
    public function setUpdateChecker(UpdateChecker $updateChecker)
    {
        $this->updateChecker = $updateChecker;
        return $this;
    }

    /**
     * @return UpdateChecker
     */
    public function getUpdateChecker()
    {
        return $this->updateChecker;
    }
}
