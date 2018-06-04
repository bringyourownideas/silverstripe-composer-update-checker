<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Link;
use Composer\Repository\ArrayRepository;
use Composer\Repository\BaseRepository;
use Composer\Repository\CompositeRepository;
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
     *
     * Packages are filtered by allowed type.
     *
     * @param array|null $allowedTypes An array of "allowed" package types. Dependencies in composer.json that do not
     *                                 match any of the given types are not returned.
     * @return array[]
     */
    public function getPackages(array $allowedTypes = null)
    {
        /** @var Composer $composer */
        $composer = $this->getComposer();

        /** @var BaseRepository $repository */
        $repository = new CompositeRepository([
            new ArrayRepository([$composer->getPackage()]),
            $composer->getRepositoryManager()->getLocalRepository(),
        ]);

        $packages = [];
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
        $originalDir = getcwd();
        chdir(BASE_PATH);
        /** @var Composer $composer */
        $composer = Factory::create(new NullIO());
        $this->setComposer($composer);
        chdir($originalDir);
    }
}
