<?php

namespace BringYourOwnIdeas\UpdateChecker\Tasks;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Composer;
use Composer\Package\Link;
use Composer\Repository\ArrayRepository;
use Composer\Repository\BaseRepository;
use Composer\Repository\CompositeRepository;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;

/**
 * Task which does the actual checking of updates
 *
 * Originally from https://github.com/XploreNet/silverstripe-composerupdates
 *
 * @author Matt Dwen
 * @license MIT
 */
class CheckComposerUpdatesTask extends BuildTask
{
    /**
     * @var string
     */
    protected $title = 'Composer update checker';

    /**
     * @var string
     */
    protected $description = 'Checks if any composer dependencies can be updated.';

    private static $segment = "CheckComposerUpdates";

    private static $dependencies = [
        'ComposerLoader' => '%$BringYourOwnIdeas\\Maintenance\\Util\\ComposerLoader',
        'UpdateChecker' => '%$BringYourOwnIdeas\\UpdateChecker\\UpdateChecker',
    ];

    /**
     * @var ComposerLoader
     */
    protected $composerLoader;

    /**
     * @var UpdateChecker
     */
    protected $updateChecker;

    /**
     * Runs the actual steps to verify if there are updates available
     *
     * @param HTTPRequest $request
     */
    public function run($request)
    {
        $packages = $this->getPackages();

        // Run through the packages and check each for updates
        foreach ($packages as $packageData) {
            $this->getUpdateChecker()->checkForUpdates(
                $packageData['package'],
                $packageData['constraint']
            );
        }

        $this->message('The task finished running. You can find the updated information in the database now.');
    }

    /**
     * Retrieve an array of primary composer dependencies from composer.json.
     *
     * Packages are filtered by allowed type.
     *
     * @return array[]
     */
    protected function getPackages()
    {
        /** @var Composer $composer */
        $composer = $this->getComposerLoader()->getComposer();

        /** @var BaseRepository $repository */
        $repository = new CompositeRepository([
            new ArrayRepository([$composer->getPackage()]),
            $composer->getRepositoryManager()->getLocalRepository(),
        ]);

        $packages = [];
        foreach ($repository->getPackages() as $package) {
            // Filter out packages that are not "allowed types"
            if (!$this->isAllowedType($package->getType())) {
                continue;
            }

            // Find the constraint used for installation
            $constraint = $this->getInstalledConstraint($repository, $package->getName());
            $packages[$package->getName()] = [
                'constraint' => $constraint,
                'package' => $package,
            ];
        }
        return $packages;
    }

    /**
     * Find all dependency constraints for the given package in the current repository and return the strictest one
     *
     * @param BaseRepository $repository
     * @param string $packageName
     * @return string
     */
    protected function getInstalledConstraint(BaseRepository $repository, $packageName)
    {
        $constraints = [];
        foreach ($repository->getDependents($packageName) as $dependent) {
            /** @var Link $link */
            list (, $link) = $dependent;
            $constraints[] = $link->getPrettyConstraint();
        }

        usort($constraints, 'version_compare');

        return array_pop($constraints);
    }

    /**
     * Check whether the package type is "allowed", which will include it in reports. If the type is not allowed
     * then the package will be skipped.
     *
     * @param string $type
     * @return bool
     */
    protected function isAllowedType($type)
    {
        $allowedTypes = (array) Config::inst()->get(UpdatePackageInfoTask::class, 'allowed_types');

        return in_array($type, $allowedTypes);
    }

    /**
     * prints a message during the run of the task
     *
     * @param string $text
     */
    protected function message($text)
    {
        if (!Director::is_cli()) {
            $text = '<p>' . $text . '</p>';
        }

        echo $text . PHP_EOL;
    }

    /**
     * @param ComposerLoader $composerLoader
     * @return $this
     */
    public function setComposerLoader(ComposerLoader $composerLoader)
    {
        $this->composerLoader = $composerLoader;
        return $this;
    }

    /**
     * @return ComposerLoader
     */
    public function getComposerLoader()
    {
        return $this->composerLoader;
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
