<?php

use BringYourOwnIdeas\UpdateChecker\ComposerLoader;
use Packagist\Api\Client;

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

    private static $dependencies = [
        'PackagistClient' => '%$Packagist\\Api\\Client',
        'ComposerLoader' => '%$BringYourOwnIdeas\\UpdateChecker\\ComposerLoader',
    ];

    /**
     * Minimum required stability defined in the site composer.json
     *
     * @var string
     */
    protected $minimumStability;

    /**
     * Whether or not to prefer stable packages
     *
     * @var bool
     */
    protected $preferStable;

    /**
     * Known stability values
     *
     * @var array
     */
    protected $stabilityOptions = array(
        'dev',
        'alpha',
        'beta',
        'rc',
        'stable'
    );

    /**
     * @var Client
     */
    protected $packagistClient;

    /**
     * @var ComposerLoader
     */
    protected $composerLoader;

    /**
     * Retrieve an array of primary composer dependencies from composer.json
     *
     * @return array
     */
    protected function getPackages()
    {
        $composerJson = $this->getComposerLoader()->getJson();

        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        // Set the stability parameters
        $this->setMinimumStability(
            isset($composerJson->{'minimum-stability'}) ? $composerJson->{'minimum-stability'} : 'stable'
        );

        $this->setPreferStable(
            isset($composerJson->{'prefer-stable'}) ? $composerJson->{'prefer-stable'} : true
        );

        $packages = [];
        foreach ($composerJson->require as $package => $version) {
            // Ensure there's a / in the name, probably not an addon with it
            if (!strpos($package, '/')) {
                continue;
            }

            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * Return an array of all Composer dependencies from composer.lock
     *
     * Example: `['silverstripe/cms' => '3.4.1', ...]`
     *
     * @return array
     */
    protected function getDependencies()
    {
        $packages = [];
        foreach ($this->getComposerLoader()->getLock()->packages as $package) {
            $packages[$package->name] = $package->version;
        }
        return $packages;
    }

    /**
     * Check if an available version is better than the current version,
     * considering stability requirements
     *
     * Returns FALSE if no update is available.
     * Returns the best available version if an update is available.
     *
     * @param string $currentVersion
     * @param string[] $availableVersions
     * @return bool|string
     */
    protected function hasUpdate($currentVersion, $availableVersions)
    {
        $currentVersion = strtolower($currentVersion);

        // Check there are some versions
        if (count($availableVersions) < 1) {
            return false;
        }

        // If this is dev-master, compare the hashes
        if ($currentVersion === 'dev-master') {
            return $this->hasUpdateOnDevMaster($availableVersions);
        }

        // Loop through each available version
        $currentStability = $this->getStability($currentVersion);
        $bestVersion = $currentVersion;
        $bestStability = $currentStability;
        $availableVersions = array_reverse($availableVersions, true);
        foreach ($availableVersions as $version => $details) {
            // Get the stability of the version
            $versionStability = $this->getStability($version);

            // Does this meet minimum stability
            if (!$this->isStableEnough($this->getMinimumStability(), $versionStability)) {
                continue;
            }

            if ($this->getPreferStable()) {
                // A simple php version compare rules out the dumb stuff
                if (version_compare($bestVersion, $version) !== -1) {
                    continue;
                }
            } else {
                // We're doing a straight version compare
                $pureBestVersion = $this->getPureVersion($bestVersion);
                $pureVersion = $this->getPureVersion($version);

                // Checkout the version
                $continue = false;
                switch (version_compare($pureBestVersion, $pureVersion)) {
                    case -1:
                        // The version is better, take it
                        break;

                    case 0:
                        // The version is the same.
                        // Do another straight version compare to rule out rc1 vs rc2 etc...
                        if ($bestStability == $versionStability) {
                            if (version_compare($bestVersion, $version) !== -1) {
                                $continue = true;
                                break;
                            }
                        }
                        break;

                    case 1:
                        // The version is worse, ignore it
                        $continue = true;
                        break;
                }

                if ($continue) {
                    continue;
                }
            }

            $bestVersion = $version;
            $bestStability = $versionStability;
        }

        if ($bestVersion !== $currentVersion || $bestStability !== $currentStability) {
            if ($bestStability === 'stable') {
                return $bestVersion;
            }

            return $bestVersion . '-' . $bestStability;
        }

        return false;
    }

    /**
     * Check the latest hash on the dev-master branch, and return it if different to the local hash
     *
     * FALSE is returned if the hash is the same.
     *
     * @param $availableVersions
     * @return bool|string
     */
    protected function hasUpdateOnDevMaster($availableVersions)
    {
        // Get the dev-master version
        $devMaster = $availableVersions['dev-master'];

        // Sneak the name of the package
        $packageName = $devMaster->getName();

        // Get the local package details
        $localPackage = $this->getLocalPackage($packageName);

        // What's the current hash?
        $localHash = $localPackage->source->reference;

        // What's the latest hash in the available versions
        $remoteHash = $devMaster->getSource()->getReference();

        // return either the new hash or false
        return ($localHash != $remoteHash) ? $remoteHash : false;
    }

    /**
     * Return details from composer.lock for a specific package
     *
     * @param string $packageName
     * @return object
     * @throws Exception if package cannot be found in composer.lock
     */
    protected function getLocalPackage($packageName)
    {
        foreach ($this->getComposerLock()->packages as $package) {
            if ($package->name == $packageName) {
                return $package;
            }
        }

        throw new Exception('Cannot locate local package ' . $packageName);
    }

    /**
     * Retrieve the pure numerical version
     *
     * @param string $version
     * @return string|null
     */
    protected function getPureVersion($version)
    {
        $matches = [];

        preg_match("/^(\d+\\.)?(\d+\\.)?(\\*|\d+)/", $version, $matches);

        if (count($matches) > 0) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Determine the stability of a given version
     *
     * @param string $version
     * @return string
     */
    protected function getStability($version)
    {
        $version = strtolower($version);

        foreach ($this->getStabilityOptions() as $option) {
            if (strpos($version, $option) !== false) {
                return $option;
            }
        }

        return 'stable';
    }

    /**
     * Return a numerical representation of a stability
     *
     * Higher is more stable
     *
     * @param string $stability
     * @return int
     * @throws Exception If the stability is unknown
     */
    protected function getStabilityIndex($stability)
    {
        $stability = strtolower($stability);

        $index = array_search($stability, $this->getStabilityOptions(), true);

        if ($index === false) {
            throw new Exception("Unknown stability: $stability");
        }

        return $index;
    }

    /**
     * Check if a stability meets a given minimum requirement
     *
     * @param $currentStability
     * @param $possibleStability
     * @return bool
     */
    protected function isStableEnough($currentStability, $possibleStability)
    {
        $minimumIndex = $this->getStabilityIndex($currentStability);
        $possibleIndex = $this->getStabilityIndex($possibleStability);

        return ($possibleIndex >= $minimumIndex);
    }

    /**
     * Record package details in the database
     *
     * @param string $package Name of the Composer Package
     * @param string $installed Currently installed version
     * @param string|boolean $latest The latest available version
     */
    protected function recordUpdate($package, $installed, $latest)
    {
        // Is there a record already for the package? If so find it.
        $packages = ComposerUpdate::get()->filter(['Name' => $package]);

        // if there is already one use it otherwise create a new data object
        if ($packages->count() > 0) {
            $update = $packages->first();
        } else {
            $update = ComposerUpdate::create();
            $update->Name = $package;
        }

        // If installed is dev-master get the hash
        if ($installed === 'dev-master') {
            $localPackage = $this->getLocalPackage($package);
            $installed = $localPackage->source->reference;
        }

        // Set the new details and save it
        $update->Installed = $installed;
        $update->Available = $latest;
        $update->write();
    }

    /**
     * runs the actual steps to verify if there are updates available
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        // Retrieve the packages
        $packages = $this->getPackages();
        $dependencies = $this->getDependencies();

        // run through the packages and check each for updates
        foreach ($packages as $package) {
            // verify that we need to check this package.
            if (!isset($dependencies[$package])) {
                continue;
            } else {
                // get information about this package from packagist.
                try {
                    $latest = $this->getPackagistClient()->get($package);
                } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
                    SS_Log::log($e->getMessage(), SS_Log::WARN);
                    continue;
                }

                // Check if there is a newer version
                $currentVersion = $dependencies[$package];
                $result = $this->hasUpdate($currentVersion, $latest->getVersions());

                // Check if there is a newer version and if so record the update
                if ($result !== false) {
                    $this->recordUpdate($package, $currentVersion, $result);
                }
            }
        }

        // finished message
        $this->message('The task finished running. You can find the updated information in the database now.');
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
     * @param string $minimumStability
     * @return $this
     */
    public function setMinimumStability($minimumStability)
    {
        $this->minimumStability = $minimumStability;
        return $this;
    }

    /**
     * @return string
     */
    public function getMinimumStability()
    {
        return $this->minimumStability;
    }

    /**
     * @param bool $preferStable
     * @return $this
     */
    public function setPreferStable($preferStable)
    {
        $this->preferStable = $preferStable;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPreferStable()
    {
        return $this->preferStable;
    }

    /**
     * @param array $stabilityOptions
     * @return $this
     */
    public function setStabilityOptions($stabilityOptions)
    {
        $this->stabilityOptions = $stabilityOptions;
        return $this;
    }

    /**
     * @return array
     */
    public function getStabilityOptions()
    {
        return $this->stabilityOptions;
    }

    /**
     * @return Client
     */
    public function getPackagistClient()
    {
        return $this->packagistClient;
    }

    /**
     * @param Client $packagistClient
     */
    public function setPackagistClient(Client $packagistClient)
    {
        $this->packagistClient = $packagistClient;
        return $this;
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
}
