<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests;

use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Composer;
use Composer\Package\RootPackage;
use PHPUnit_Framework_TestCase;
use SilverStripe\Core\Injector\Injector;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class UpdateCheckerTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testAvailableUpdatesAreWrittenToPackageModel()
    {
        // Mock Composer
        $composerMock = $this->getMockBuilder(Composer::class)->getMock();
        Injector::inst()->registerService($composerMock, Composer::class);

        // Create mock package
        $packageMock = $this->getMockBuilder(RootPackage::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getPrettyVersion', 'getSourceReference'])
            ->getMock();
        $packageMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('foo/module'));
        $packageMock->expects($this->exactly(3))
            ->method('getPrettyVersion')
            ->will($this->onConsecutiveCalls('1.2.0', '1.2.3', '2.3.4'));
        $packageMock->expects($this->exactly(3))
            ->method('getSourceReference')
            ->will($this->onConsecutiveCalls('1a2s3d4f', '8s7d6f5g', '9g8f7d6s'));

        // Partially mock the update checker
        $checker = $this->getMockBuilder(UpdateChecker::class)->setMethods(['findLatestPackage'])->getMock();
        $checker->expects($this->exactly(2))
            ->method('findLatestPackage')
            ->will($this->returnValue($packageMock));

        // Run the checker
        $checker->checkForUpdates($packageMock, '~1.2');

        // Check the database for results
        $module = Package::get()->filter(['Name' => 'foo/module'])->first();
        $this->assertNotEmpty($module);

        $this->assertSame('1a2s3d4f', $module->VersionHash, 'The current hash is recorded');
        $this->assertSame('8s7d6f5g', $module->AvailableHash, 'The next available hash is recorded');
        $this->assertSame('9g8f7d6s', $module->LatestHash, 'The latest available hash is recorded');

        $this->assertSame('1.2.0', $module->Version, 'The current version is recorded');
        $this->assertSame('1.2.3', $module->AvailableVersion, 'The available version is recorded');
        $this->assertSame('2.3.4', $module->LatestVersion, 'The latest available version is recorded');

        $this->assertSame('~1.2', $module->VersionConstraint, 'The installation constraint is recorded');
    }
}
