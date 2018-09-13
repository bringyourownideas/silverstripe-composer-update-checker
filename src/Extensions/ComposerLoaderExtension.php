<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Link;
use Composer\Repository\ArrayRepository;
use Composer\Repository\BaseRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;

class ComposerLoaderExtension extends Extension
{
    use Configurable;

    /**
     * Set to a custom directory for Composer's '.composer' cache directory. This will only be used if the
     * `COMPOSER_HOME` environment variable is not defined and `HOME` is not defined or is not writable
     *
     * @config
     * @var string
     */
    private static $composer_cache_directory = '/tmp';

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
     *
     * Packages are filtered by allowed type.
     *
     * @param array|null $allowedTypes An array of "allowed" package types. Dependencies in composer.json that do not
     *                                 match any of the given types are not returned.
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
     *
     * @return RepositoryInterface
     */
    protected function getRepository()
    {
        /** @var Composer $composer */
        $composer = $this->getComposer();

        /** @var BaseRepository $repository */
        return new CompositeRepository([
            new ArrayRepository([$composer->getPackage()]),
            $composer->getRepositoryManager()->getLocalRepository(),
        ]);
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
     * Builds an instance of Composer
     */
    public function onAfterBuild()
    {
        if (!Environment::getEnv('COMPOSER_HOME')) {
            // Check `HOME` and if it's writable (then we can let that be used).
            $home = Environment::getEnv('HOME');
            if (!$home || !is_dir($home) || !is_writable($home)) {
                // Set our own directory
                $composerCacheDirectory = $this->config()->get('composer_cache_directory');
                putenv('COMPOSER_HOME=' . $composerCacheDirectory);
            }
        }

        $originalDir = getcwd();
        chdir(BASE_PATH);
        /** @var Composer $composer */
        $composer = Factory::create(new NullIO());
        $this->setComposer($composer);
        chdir($originalDir);
    }
}
