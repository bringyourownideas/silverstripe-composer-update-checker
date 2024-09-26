<?php

namespace BringYourOwnIdeas\UpdateChecker\Tests\Extensions;

use BringYourOwnIdeas\Maintenance\Tasks\UpdatePackageInfoTask;
use BringYourOwnIdeas\UpdateChecker\Extensions\CheckComposerUpdatesExtension;
use BringYourOwnIdeas\UpdateChecker\Extensions\ComposerLoaderExtension;
use BringYourOwnIdeas\UpdateChecker\Tests\Stubs\ComposerLoaderExtensionStub;
use BringYourOwnIdeas\UpdateChecker\UpdateChecker;
use Composer\Package\PackageInterface;
use PHPUnit_Framework_TestCase;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\ArrayInput;

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

    protected function setUp(): void
    {
        parent::setUp();

        // Register the extension stub for unit testing to avoid loading Composer
        Config::modify()->merge(Injector::class, ComposerLoaderExtension::class, [
            'class' => ComposerLoaderExtensionStub::class,
        ]);

        // Create the task for testing
        $this->task = UpdatePackageInfoTask::create();

        // Create a partial mock of the update checker
        $updateCheckerMock = $this->getMockBuilder(UpdateChecker::class)->onlyMethods(['checkForUpdates'])->getMock();
        $this->task->setUpdateChecker($updateCheckerMock);

        $this->allowedTypes = ['silverstripe-module', 'silverstripe-vendormodule', 'silverstripe-theme'];
        Config::modify()->set(UpdatePackageInfoTask::class, 'allowed_types', $this->allowedTypes);
    }

    public function testRunPassesPackagesToUpdateChecker()
    {
        $this->task->getUpdateChecker()->expects($this->atLeastOnce())
            ->method('checkForUpdates')
            ->with($this->isInstanceOf(PackageInterface::class), $this->isType('string'))
            ->willReturn([]);

        $this->runTask();
    }

    public function testOnlyAllowedPackageTypesAreProcessed()
    {
        $this->task->getUpdateChecker()->expects($this->atLeastOnce())
            ->method('checkForUpdates')
            ->with($this->callback(function ($argument) {
                return in_array($argument->getType(), $this->allowedTypes);
            }))
            ->willReturn([]);

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
        $output = PolyOutput::create(PolyOutput::FORMAT_ANSI);
        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $this->task->run($input, $output);
        return ob_get_clean();
    }
}
