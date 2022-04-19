<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests\Stubs;

use BringYourOwnIdeas\UpdateChecker\Extensions\ComposerLoaderExtension;
use Composer\Package\Package;
use Composer\Repository\ArrayRepository;
use Composer\Repository\BaseRepository;
use Composer\Repository\InstalledRepository;
use Composer\Repository\InstalledArrayRepository;
use SilverStripe\Dev\TestOnly;

/**
 * A partially stubbed version of the {@link ComposerLoaderExtension} extension which doesn't rely on Composer
 */
class ComposerLoaderExtensionStub extends ComposerLoaderExtension implements TestOnly
{
    protected function getRepository()
    {
        $vendorModule = new Package('silverstripe/framework', '4.1.1.0', '4.1.1');
        $vendorModule->setType('silverstripe-vendormodule');

        $silverstripeModule = new Package('silverstripe-themes/simple', '2.1.0.1', '2.1.0');
        $silverstripeModule->setType('silverstripe-module');

        $generalPackage = new Package('something/unrelated', '1.2.3.4', '1.2.3');
        $generalPackage->setType('package');

        // InstalledRepository is only available in composer v2
        if (class_exists(InstalledRepository::class)) {
            return new InstalledRepository([
                new InstalledArrayRepository([$vendorModule, $silverstripeModule, $generalPackage])
            ]);
        } else {
            return new ArrayRepository([$vendorModule, $silverstripeModule, $generalPackage]);
        }
    }

    protected function getConstraint(InstalledRepository $repository, string $packageName): string
    {
        return $this->getPackageConstraint($packageName);
    }

    protected function getInstalledConstraint(BaseRepository $repository, $packageName)
    {
        return $this->getPackageConstraint($packageName);
    }

    private function getPackageConstraint($packageName)
    {
        switch ($packageName) {
            case 'silverstripe/framework':
                return '4.1.1';
            case 'silverstripe-themes/simple':
                return '~2.1.0';
            case 'something/unrelated':
                return '^1.0';
            default:
                return '';
        }
    }

    public function onAfterBuild()
    {
        // noop - don't load local Composer repository
    }
}
