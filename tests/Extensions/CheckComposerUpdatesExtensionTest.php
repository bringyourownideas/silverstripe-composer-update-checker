<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests\Extensions;

use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Package\PackageInterface;
use Config;
use PHPUnit_Framework_TestCase;
use SapphireTest;
use UpdatePackageInfoTask;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class CheckComposerUpdatesExtensionTest extends SapphireTest
{
    /**
     * @var UpdatePackageInfoTask
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

        $updateCheckerMock = $this->getMockBuilder(UpdateChecker::class)->setMethods(['checkForUpdates'])->getMock();
        $this->task->setUpdateChecker($updateCheckerMock);

        $this->allowedTypes = ['silverstripe-module', 'silverstripe-vendormodule', 'silverstripe-theme'];
        Config::inst()->update(UpdatePackageInfo::class, 'allowed_types', $this->allowedTypes);
    }

    public function testRunPassesPackagesToUpdateChecker()
    {
        $this->task->getUpdateChecker()->expects($this->atLeastOnce())
            ->method('checkForUpdates')
            ->with($this->isInstanceOf(PackageInterface::class), $this->isType('string'));

        $output = $this->runTask();
        $this->assertContains('The task finished running', $output);
    }

    public function testOnlyAllowedPackageTypesAreProcessed()
    {
        $this->task->getUpdateChecker()->expects($this->atLeastOnce())
            ->method('checkForUpdates')
            ->with($this->callback(function ($argument) {
                return in_array($argument->getType(), $this->allowedTypes);
            }));

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
