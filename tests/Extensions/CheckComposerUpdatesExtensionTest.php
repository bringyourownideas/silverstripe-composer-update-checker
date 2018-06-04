<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests\Extensions;

use BringYourOwnIdeas\UpdateChecker\Extensions\CheckComposerUpdatesExtension;
use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Package\PackageInterface;
use PHPUnit_Framework_TestCase;
use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class CheckComposerUpdatesExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * @var UpdatePackageInfoTask|CheckComposerUpdatesExtension
     */
    protected $task;

    /**
     * @var string[]
     */
    protected $allowedTypes;

    public function setUp()
    {
        parent::setUp();

        $this->task = UpdatePackageInfoTask::create();

        // Create a partial mock of the update checker
        $updateCheckerMock = $this->getMockBuilder(UpdateChecker::class)->setMethods(['checkForUpdates'])->getMock();
        $this->task->setUpdateChecker($updateCheckerMock);

        $this->allowedTypes = ['silverstripe-module', 'silverstripe-vendormodule', 'silverstripe-theme'];
        Config::inst()->update(UpdatePackageInfoTask::class, 'allowed_types', $this->allowedTypes);
    }

    public function testRunPassesPackagesToUpdateChecker()
    {
        $this->task->getUpdateChecker()->expects($this->atLeastOnce())
            ->method('checkForUpdates')
            ->with($this->isInstanceOf(PackageInterface::class), $this->isType('string'))
            ->will($this->returnValue([]));

        $this->runTask();
    }

    public function testOnlyAllowedPackageTypesAreProcessed()
    {
        $this->task->getUpdateChecker()->expects($this->atLeastOnce())
            ->method('checkForUpdates')
            ->with($this->callback(function ($argument) {
                return in_array($argument->getType(), $this->allowedTypes);
            }))
            ->will($this->returnValue([]));

        $this->runTask();
    }

    /**
     * Runs the task and buffers the output (tasks output directly)
     *
     * @return string Task output
     */
    protected function runTask()
    {
        ob_start();
        $this->task->run(null);
        return ob_get_clean();
    }
}
