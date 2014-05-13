<?php

  class CheckComposerUpdates extends CliController {

    #region Declarations

    /**
     * Deserialized JSON from composer.lock
     * @var object
     */
    private $composerLock;

    /**
     * Minimum required stability defined in the site composer.json
     * @var string
     */
    private $minimumStability;

    /**
     * Whether or not to prefer stable packages
     * @var bool
     */
    private $preferStable;

    /**
     * Known stability values
     * @var array(string)
     */
    private $stabilityOptions = array(
        'dev',
        'alpha',
        'beta',
        'rc',
        'stable'
    );

    /**
     * Whether to write all log messages or not
     * @var bool
     */
    private $extendedLogging = true;

    #endregion Declarations

    #region Private Methods

    /**
     * Retrieve an array of primary composer dependencies from composer.json
     *
     * @return array
     */
    private function getPackages() {
      $composerPath = BASE_PATH . '/composer.json';
      if (!file_exists($composerPath)) {
        return null;
      }

      // Read the contents of composer.json
      $composerFile = file_get_contents($composerPath);

      // Parse the json
      $composerJson = json_decode($composerFile);

      ini_set('display_errors', 1);
      error_reporting(E_ALL);

      // Set the stability parameters
      $this->minimumStability = (isset($composerJson->{'minimum-stability'}))
          ? $composerJson->{'minimum-stability'}
          : 'stable';
      $this->logMessage('Minimum stability: ' . $this->minimumStability, true);

      $this->preferStable = (isset($composerJson->{'prefer-stable'}))
          ? $composerJson->{'prefer-stable'}
          : true;
      $this->logMessage('Prefer stable: ' . $this->preferStable, true);

      $packages = array();
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
     * @return array(package => hash)
     */
    private function getDependencies() {
      $composerPath = BASE_PATH . '/composer.lock';
      if (!file_exists($composerPath)) {
        return null;
      }

      // Read the contents of composer.json
      $composerFile = file_get_contents($composerPath);

      // Parse the json
      $dependencies = json_decode($composerFile);

      $packages = array();

      // Loop through the requirements
      foreach ($dependencies->packages as $package) {
        $packages[$package->name] = $package->version;
      }

      $this->composerLock = $dependencies;

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
     * @param string $availableVersions
     * @return bool|string
     */
    private function hasUpdate($currentVersion, $availableVersions) {
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
      foreach($availableVersions as $version => $details) {
        // Get the stability of the version
        $versionStability = $this->getStability($version);

        // Does this meet minimum stability
        if (!$this->isStableEnough($this->minimumStability, $versionStability)) {
          continue;
        }

        if ($this->preferStable) {
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
    private function hasUpdateOnDevMaster($availableVersions) {
      // Get the dev-master version
      $devMaster = $availableVersions['dev-master'];

      // Sneak the name of the package
      $packageName = $devMaster->getName();

      // Get the local package details
      $localPackage = $this->getLocalPackage($packageName);

      // What's the current hash?
      $localHash = $localPackage->source->reference;
      $this->logMessage('Local hash: ' . $localHash, true);

      // What's the latest hash in the available versions
      $remoteHash = $devMaster->getSource()->getReference();
      $this->logMessage('Remote hash: ' . $remoteHash, true);

      if ($localHash != $remoteHash) {
        return $remoteHash;
      }

      return false;
    }

    /**
     * Return details from composer.lock for a specific package
     *
     * @param string $packageName
     * @return object
     * @throws Exception if package cannot be found in composer.lock
     */
    private function getLocalPackage($packageName) {
      foreach($this->composerLock->packages as $package) {
        if ($package->name == $packageName) {
          return $package;
        }
      }

      throw new Exception("Cannot locate local package " . $packageName);
    }

    /**
     * Retrieve the pure numerical version
     *
     * @param string $version
     * @return string
     */
    private function getPureVersion($version) {
      $matches = array();

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
    private function getStability($version) {
      $version = strtolower($version);

      foreach($this->stabilityOptions as $option) {
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
    private function getStabilityIndex($stability) {
      $stability = strtolower($stability);

      $index = array_search($stability, $this->stabilityOptions, true);

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
    private function isStableEnough($currentStability, $possibleStability) {
      $minimumIndex = $this->getStabilityIndex($currentStability);
      $possibleIndex = $this->getStabilityIndex($possibleStability);

      return ($possibleIndex >= $minimumIndex);
    }

    /**
     * Write a log message
     *
     * @param $message
     * @param bool $extended
     */
    private function logMessage($message, $extended = false) {
      if (!$extended || $this->extendedLogging) {
        echo $message . PHP_EOL;
      }
    }

    /**
     * Record package details in the database
     *
     * @param string $package Name of the Composer Package
     * @param string $installed Currently installed version
     * @param string $latest The latest available version
     * @return bool TRUE if the package can be updated
     */
    private function recordUpdate($package, $installed, $latest) {
      // Is there a record already for the package?
      $model = ComposerUpdate::get()
          ->find('Name', $package);

      if (!$model) {
        $model = new ComposerUpdate();
        $model->Name = $package;
      }

      // What was the last known update
      $lastKnown = $model->Available;

      // If installed is dev-master, get the hash
      if ($installed === 'dev-master') {
        $localPackage = $this->getLocalPackage($package);
        $installed = $localPackage->source->reference;
      }

      // If latest is false, make it the same as installed
      if ($latest === false) {
        $latest = $installed;
      }

      // Set the new details
      $model->Installed = $installed;
      $model->Available = $latest;

      // Save it
      $model->write();

      // Is the latest different to the last known?
      if ($latest != $lastKnown) {
        // Is it different to what's installed?
        if ($latest != $installed) {
          // It's an update!
          return true;
        }
      }

      // It's not an update
      return false;
    }

    /**
     * Send email notification of available updates
     *
     * @param array $updates
     */
    private function emailUpdates($updateArray) {
      // If there's no updates we don't care
      if (count($updateArray) < 1) {
        return;
      };

      $this->logMessage(PHP_EOL . 'Sending update email', true);

      // Convert the updates to an ArrayList
      $updates = ArrayList::create();
      foreach ($updateArray as $update) {
        $updates->add(ArrayData::create(array(
          'Package' => $update['package'],
          'Latest' => $update['latest']
        )));
      }

      // Site details
      $siteName = SiteConfig::current_site_config()->Title;

      // Create the email
      $email = new Email();
      $email->setSubject('[SilverStripe] Updates for ' . $siteName);

      // Set the template
      $email->setTemplate('ComposerUpdateEmail');

      // Fill with what we know
      $email->populateTemplate(array(
        'Updates' => $updates,
        'SiteName' => $siteName
      ));

      // Who are sending this to?
      $notify = Config::inst()->get('ComposerUpdates', 'notify');
      if (isset($notify) && is_array($notify)) {
        foreach ($notify as $to) {
          $email->setTo($to);
          $email->send();
        }
      }
    }

    #endregion Private Methods

    #region Public Methods

    public function process() {
      // Retrieve the packages
      $packages = $this->getPackages();
      $dependencies = $this->getDependencies();

      // Load the Packagist API
      require_once(BASE_PATH . '/vendor/autoload.php');
      $packagist = new Packagist\Api\Client();

      // Record updates we need to notify about
      $updates = array();

      // Loop through each package
      foreach($packages as $package) {
        echo PHP_EOL;
        $this->logMessage('Checking ' . $package);

        // Get the currentVersion
        if (!isset($dependencies[$package])) {
          $this->logMessage($package . ' cannot be found in composer.json');
          continue;
        } else {
          $currentVersion = $dependencies[$package];
          $currentStability = $this->getStability($currentVersion);
          $versionDesc = $currentVersion;
          if ($currentStability !== 'stable') {
            $versionDesc .= '-' . $currentStability;
          }
          $this->logMessage('Installed: ' . $versionDesc, true);
        }

        // Get the details from packagist
        try {
          $latest = $packagist->get($package);
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $ex) {
          $this->logMessage($ex->getMessage());
          continue;
        }

        $versions = $latest->getVersions();

        // Check if there is a newer version
        $result = $this->hasUpdate($currentVersion, $versions);

        if ($result === false) {
          $this->logMessage('Up to date');
        } else {
          $this->logMessage('Update to ' . $result);
        }

        // Record the result and check if any update is newer than one we knew about before
        $update = $this->recordUpdate($package, $currentVersion, $result);

        if ($update) {
          $updates[$package] = array(
            'package' => $package,
            'installed' => $versionDesc,
            'latest' => $result
          );
        }
      }

      // Email updates
      $this->emailUpdates($updates);
    }

    #endregion Public Methods

  }
