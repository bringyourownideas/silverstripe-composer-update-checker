<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests;

use BringYourOwnIdeas\Maintenance\Util\ComposerLoader;
use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Composer;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class UpdateCheckerTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * @var UpdateChecker
     */
    protected $updateChecker;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock composer and composer loader
        $composer = $this->getMockBuilder(Composer::class)->getMock();
        $composerLoader = new class ($composer) extends ComposerLoader {
            private $composer;
            public function __construct($composer)
            {
                $this->composer = $composer;
            }
            public function getComposer(): Composer
            {
                return $this->composer;
            }
        };
        Injector::inst()->registerService($composerLoader, ComposerLoader::class);

        // Partially mock UpdateChecker
        $this->updateChecker = $this->getMockBuilder(UpdateChecker::class)
            ->onlyMethods(['findLatestPackage'])
            ->getMock();
    }

    public function testCheckForUpdates()
    {
        $mockPackage = new \Composer\Package\Package('foo/bar', '2.3.4.0', '2.3.4');
        $mockPackage->setSourceReference('foobar123');
        $this->updateChecker
            ->expects($this->exactly(2))
            ->method('findLatestPackage')
            ->willReturnOnConsecutiveCalls(
                false,
                $mockPackage,
            );
        $result = $this->updateChecker->checkForUpdates($mockPackage, '~1.2.0');
        $this->assertArrayNotHasKey('AvailableVersion', $result, 'No available update is recorded');
        $this->assertArrayNotHasKey('AvailableHash', $result, 'No available update is recorded');
        $this->assertSame('2.3.4', $result['LatestVersion'], 'Latest version is returned');
        $this->assertSame('foobar123', $result['LatestHash'], 'Hash of latest version is returned');
    }
}
