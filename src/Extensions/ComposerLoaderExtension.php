<?php

namespace BringYourOwnIdeas\UpdateChecker\Extensions;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use SilverStripe\Core\Extension;

class ComposerLoaderExtension extends Extension
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param Composer $composer
     * @return $this
     */
    public function setComposer(Composer $composer)
    {
        $this->composer = $composer;
        return $this;
    }

    /**
     * @return Composer
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * Builds an instance of Composer
     */
    public function onAfterBuild()
    {
        $originalDir = getcwd();
        chdir(BASE_PATH);
        /** @var Composer $composer */
        $composer = Factory::create(new NullIO());
        $this->setComposer($composer);
        chdir($originalDir);
    }
}
