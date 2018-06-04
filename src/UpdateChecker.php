<?php

namespace BringYourOwnIdeas\UpdateChecker;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use Composer\Composer;
use Composer\DependencyResolver\Pool;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use SilverStripe\Core\Injector\Injector;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\ORM\DataObject;

/**
 * The update checker class is provided a {@link Link} object representing a package and uses the Composer API to
 * determine the next available updates for the package.
 */
class UpdateChecker
{
    /**
     * @var VersionSelector
     */
    protected $versionSelector;

    /**
     * Update types (see {@link ComposerPackageVersion}
     *
     * @var string
     */
    const TYPE_AVAILABLE = 'Available';
    const TYPE_LATEST = 'Latest';

    /**
     * Checks the given package for available and latest updates, and writes them to data models if found
     *
     * @param PackageInterface $package
     * @param string $constraint
     */
    public function checkForUpdates(PackageInterface $package, $constraint)
    {
        $installedVersion = $package->getPrettyVersion();

        /** @var Composer $composer */
        $composer = Injector::inst()->create(ComposerLoader::class)->getComposer();

        $updateInformation = [
            'Version' => $installedVersion,
            'VersionHash' => $package->getSourceReference(),
            'VersionConstraint' => $constraint,
        ];

        if ($available = $this->findLatestPackage($package, $constraint, $installedVersion, $composer, true)) {
            $updateInformation[self::TYPE_AVAILABLE . 'Version'] = $available->getPrettyVersion();
            $updateInformation[self::TYPE_AVAILABLE . 'Hash'] = $available->getSourceReference();
        }

        if ($latest = $this->findLatestPackage($package, $constraint, $installedVersion, $composer, false)) {
            $updateInformation[self::TYPE_LATEST . 'Version'] = $latest->getPrettyVersion();
            $updateInformation[self::TYPE_LATEST . 'Hash'] = $latest->getSourceReference();
        }

        $this->recordUpdate($package->getName(), $updateInformation);
    }

    /**
     * @param Composer $composer
     * @return VersionSelector
     */
    protected function getVersionSelector(Composer $composer)
    {
        if (!$this->versionSelector) {
            // Instantiate a new repository pool, providing the stability flags from the project
            $pool = new Pool(
                $composer->getPackage()->getMinimumStability(),
                $composer->getPackage()->getStabilityFlags()
            );
            $pool->addRepository(new CompositeRepository($composer->getRepositoryManager()->getRepositories()));

            $this->versionSelector = new VersionSelector($pool);
        }

        return $this->versionSelector;
    }

    /**
     * Given a package, this finds the latest package matching it
     *
     * Based on Composer's ShowCommand::findLatestPackage
     *
     * @param PackageInterface $package
     * @param string $constraint
     * @param string $installedVersion
     * @param Composer $composer
     * @param bool $minorOnly
     * @return bool|PackageInterface
     */
    protected function findLatestPackage(
        PackageInterface $package,
        $constraint,
        $installedVersion,
        Composer $composer,
        $minorOnly = false
    ) {
        // find the latest version allowed in this pool
        $name = $package->getName();
        $versionSelector = $this->getVersionSelector($composer);
        $stability = $composer->getPackage()->getMinimumStability();
        $flags = $composer->getPackage()->getStabilityFlags();
        if (isset($flags[$name])) {
            $stability = array_search($flags[$name], BasePackage::$stabilities, true);
        }

        $bestStability = $stability;
        if ($composer->getPackage()->getPreferStable()) {
            $bestStability = $composer->getPackage()->getStability();
        }

        $targetVersion = null;
        if (0 === strpos($installedVersion, 'dev-')) {
            $targetVersion = $installedVersion;
        }

        if ($targetVersion === null && $minorOnly) {
            // Use the semver constraint to determine the next available version
            $targetVersion = $constraint;
        }

        return $versionSelector->findBestCandidate($name, $targetVersion, null, $bestStability);
    }

    /**
     * Record package details in the database
     *
     * @param string $package Name of the Composer Package
     * @param array $updateInformation Data to write to the model
     */
    protected function recordUpdate($package, array $updateInformation)
    {
        // Is there a record already for the package? If so find it.
        $packages = Package::get()->filter(['Name' => $package]);

        // if there is already one use it otherwise create a new data object
        if ($packages->count() > 0) {
            $update = $packages->first();
        } else {
            $update = Package::create();
            $update->Name = $package;
        }

        /** @var DataObject $update */
        $update->update($updateInformation)->write();
    }
}
