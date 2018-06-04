<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Composer;
use PHPUnit_Framework_TestCase;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class UpdateCheckerTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $updateChecker;

    public function setUp()
    {
        parent::setUp();

        // Mock composer and composer loader
        $composer = $this->getMockBuilder(Composer::Class)->getMock();
        $composerLoader = $this->getMockBuilder(ComposerLoader::class)
            ->setMethods(['getComposer'])
            ->getMock();
        $composerLoader->expects($this->once())->method('getComposer')->will($this->returnValue($composer));
        Injector::inst()->registerService($composerLoader, ComposerLoader::class);

        // Partially mock UpdateChecker
        $this->updateChecker = $this->getMockBuilder(UpdateChecker::class)
            ->setMethods(['findLatestPackage'])
            ->getMock();
    }

    public function testCheckForUpdates()
    {
        $mockPackage = new \Composer\Package\Package('foo/bar', '2.3.4.0', '2.3.4');
        $mockPackage->setSourceReference('foobar123');

        // No available update
        $this->updateChecker->expects($this->at(0))
            ->method('findLatestPackage')
            ->will($this->returnValue(false));

        // There is latest version though
        $this->updateChecker->expects($this->at(1))
            ->method('findLatestPackage')
            ->will($this->returnValue($mockPackage));


        $result = $this->updateChecker->checkForUpdates($mockPackage, '~1.2.0');
        $this->assertArrayNotHasKey('AvailableVersion', $result, 'No available update is recorded');
        $this->assertArrayNotHasKey('AvailableHash', $result, 'No available update is recorded');
        $this->assertSame('2.3.4', $result['LatestVersion'], 'Latest version is returned');
        $this->assertSame('foobar123', $result['LatestHash'], 'Hash of latest version is returned');
    }
}
