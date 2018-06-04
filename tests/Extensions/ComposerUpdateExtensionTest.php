<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests\Extensions;

use BringYourOwnIdeas\UpdateChecker\Extensions\ComposerUpdateExtension;
use PHPUnit_Framework_TestCase;
use BringYourOwnIdeas\Maintenance\Model\Package;
use SilverStripe\Dev\SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class ComposerUpdateExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'ComposerUpdateExtensionTest.yml';

    protected $requiredExtensions = [
        Package::class => [
            ComposerUpdateExtension::class,
        ],
    ];

    public function testAvailableVersionIsNotShownWhenSameAsCurrent()
    {
        /** @var Package|ComposerUpdateExtension $package */
        $package = $this->objFromFixture(Package::class, 'up_to_date');
        $this->assertEmpty($package->getAvailableVersion());
    }

    public function testAvailableVersionIsShown()
    {
        /** @var Package|ComposerUpdateExtension $package */
        $package = $this->objFromFixture(Package::class, 'has_available_update');
        $this->assertSame('1.2.1', $package->getAvailableVersion());
    }
}
