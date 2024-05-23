<?php

namespace BringYourOwnIdeas\UpdateChecker;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\VcsRepository;
use ReflectionObject;
use RuntimeException;

class DriverReflection
{
    public static function getDriverWithoutException(VcsRepository $repo, IOInterface $io, Config $config)
    {
        $reflectedRepo = new ReflectionObject($repo);
        $drivers = static::getRepoField($repo, $reflectedRepo, 'drivers');

        if (isset($drivers[static::getRepoField($repo, $reflectedRepo, 'type')])) {
            $class = $drivers[static::getRepoField($repo, $reflectedRepo, 'type')];
            $driver = new $class($repo->getRepoConfig(), $io, $config);
            try {
                $driver->initialize();
            } catch (RuntimeException $e) {
                // no-op - this is probably caused due to insufficient permissions when trying to create /var/www/.ssh
                // but since we're just getting the driver as a shortcut to getting the repository name, we can ignore this for now.
            }
            return $driver;
        }

        foreach ($drivers as $driver) {
            if ($driver::supports($io, $config, static::getRepoField($repo, $reflectedRepo, 'url'))) {
                $driver = new $driver($repo->getRepoConfig(), $io, $config);
                try {
                    $driver->initialize();
                } catch (RuntimeException $e) {
                    // no-op - this is probably caused due to insufficient permissions when trying to create /var/www/.ssh
                    // but since we're just getting the driver as a shortcut to getting the repository name, we can ignore this for now.
                }
                return $driver;
            }
        }

        foreach ($drivers as $driver) {
            if ($driver::supports($io, $config, static::getRepoField($repo, $reflectedRepo, 'url'), true)) {
                $driver = new $driver($repo->getRepoConfig(), $io, $config);
                try {
                    $driver->initialize();
                } catch (RuntimeException $e) {
                    // no-op - this is probably caused due to insufficient permissions when trying to create /var/www/.ssh
                    // but since we're just getting the driver as a shortcut to getting the repository name, we can ignore this for now.
                }
                return $driver;
            }
        }
    }

    public static function getSshUrl($driver)
    {
        $reflectedDriver = new ReflectionObject($driver);
        if ($reflectedDriver->hasMethod('generateSshUrl')) {
            $reflectedMethod = $reflectedDriver->getMethod('generateSshUrl');
            $reflectedMethod->setAccessible(true);
            return $reflectedMethod->invoke($driver);
        }
        return null;
    }

    protected static function getRepoField(VcsRepository $repo, ReflectionObject $reflectedRepo, string $field)
    {
        $reflectedUrl = $reflectedRepo->getProperty($field);
        $reflectedUrl->setAccessible(true);
        return $reflectedUrl->getValue($repo);
    }
}
