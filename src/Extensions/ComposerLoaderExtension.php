<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\UpdateChecker\DriverReflection;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Link;
use Composer\Repository\InstalledRepository;
use Composer\Repository\RootPackageRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\Vcs\VcsDriverInterface;
use Composer\Repository\VcsRepository;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;

class ComposerLoaderExtension extends Extension
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param Composer $composer
     * @return $this
     */
    public function setComposer(Composer $composer)
    {
        $this->composer = $composer;
        return $this;
    }

    /**
     * @return Composer
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * Retrieve an array of primary composer dependencies from composer.json.
     * Packages are filtered by allowed type.
     * Dependencies in composer.json that do not match any of the given types are not returned.
     *
     * @param array|null $allowedTypes An array of "allowed" package types.
     * @return array[]
     */
    public function getPackages(array $allowedTypes = null)
    {
        $packages = [];
        $repository = $this->getRepository();
        foreach ($repository->getPackages() as $package) {
            // Filter out packages that are not "allowed types"
            if (is_array($allowedTypes) && !in_array($package->getType(), $allowedTypes)) {
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
     * Provides access to the Composer repository
     */
    protected function getRepository(): RepositoryInterface
    {
        /** @var Composer $composer */
        $composer = $this->getComposer();
        return new InstalledRepository([
            new RootPackageRepository($composer->getPackage()),
            $composer->getRepositoryManager()->getLocalRepository()
        ]);
    }

    /**
     * Find all dependency constraints for the given package in the current repository and return the strictest one
     */
    protected function getInstalledConstraint(InstalledRepository $repository, string $packageName): string
    {
        $constraints = [];
        foreach ($repository->getDependents($packageName) as $dependent) {
            /** @var Link $link */
            list (, $link) = $dependent;
            $constraints[] = $link->getPrettyConstraint() ?? '';
        }
        usort($constraints, 'version_compare');
        return array_pop($constraints);
    }

    /**
     * Builds an instance of Composer
     */
    public function onAfterBuild()
    {
        // Mock COMPOSER_HOME if it's not defined already. Composer requires one of the two to be set.
        if (!Environment::getEnv('COMPOSER_HOME')) {
            $home = Environment::getEnv('HOME');
            if (!$home || !is_dir($home) || !is_writable($home)) {
                // Set our own directory
                putenv('COMPOSER_HOME=' . sys_get_temp_dir());
            }
        }

        $originalDir = getcwd();
        chdir(BASE_PATH);
        /** @var Composer $composer */
        $composer = Factory::create($io = new NullIO());

        // Don't include inaccessible repositories.
        $inaccessiblePackages = (array)UpdatePackageInfoTask::config()->get('inaccessible_packages');
        $inaccessibleHosts = (array)UpdatePackageInfoTask::config()->get('inaccessible_repository_hosts');
        if (!empty($inaccessiblePackages) || !empty($inaccessibleHosts)) {
            $oldManager = $composer->getRepositoryManager();
            $manager = new RepositoryManager(
                $io,
                $composer->getConfig(),
                $composer->getEventDispatcher(),
                Factory::createRemoteFilesystem($io, $composer->getConfig())
            );
            $manager->setLocalRepository($oldManager->getLocalRepository());
            foreach ($oldManager->getRepositories() as $repo) {
                if ($repo instanceof VcsRepository) {
                    /** @var VcsDriverInterface $driver */
                    $driver = DriverReflection::getDriverWithoutException($repo, $io, $composer->getConfig());
                    $sshUrl = DriverReflection::getSshUrl($driver);
                    $sourceURL = $driver->getUrl();
                    $package = $this->findPackageByUrl($sourceURL);
                    if (!$package && $sshUrl) {
                        $package = $this->findPackageByUrl($sshUrl);
                    }
                    // Don't add the repository if we can confirm it's inaccessible.
                    // Otherwise the UpdateChecker will attempt to fetch packages using the VcsDriver.
                    if (
                        ($package && in_array($package->name, $inaccessiblePackages))
                        || in_array(parse_url($sourceURL, PHP_URL_HOST), $inaccessibleHosts)
                        || ($sshUrl && in_array(preg_replace('/^([^@]+@)?([^:]+):.*/', '$2', $sshUrl), $inaccessibleHosts))
                    ) {
                        continue;
                    }
                }
                $manager->addRepository($repo);
            }
            $composer->setRepositoryManager($manager);
        }

        $this->setComposer($composer);
        chdir($originalDir);
    }

    public function findPackageByUrl(string $url, bool $includeDev = true)
    {
        $lock = $this->owner->getLock();
        foreach ($lock->packages as $package) {
            if (isset($package->source->url) && $package->source->url === $url) {
                return $package;
            }
            if (isset($package->dist->url) && $package->dist->url === $url) {
                return $package;
            }
        }
        if ($includeDev) {
            foreach ($lock->{'packages-dev'} as $package) {
                if (isset($package->source->url) && $package->source->url === $url) {
                    return $package;
                }
                if (isset($package->dist->url) && $package->dist->url === $url) {
                    return $package;
                }
            }
        }
    }
}
