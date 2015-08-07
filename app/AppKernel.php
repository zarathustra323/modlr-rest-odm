<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    /**
     * {@inheritDoc}
     */
    public function registerBundles()
    {
        $bundles = [];

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {

        }
        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // // Load the core/global config
        // $loader->load($this->getGlobalConfigFile());

        // // Load the core/global config overrides by account/group
        // foreach ($this->getGlobalConfigOverrides() as $file) {
        //     $this->loadIfExists($file, $loader);
        // }

        // // Load application config
        // $loader->load($this->getAppConfigFile());

        // // Load theme config
        // $this->loadIfExists($this->getThemeConfigFile(), $loader);

        // // Load account and group application overrides (by app and theme)
        // foreach ($this->getAppConfigOverrides() as $file) {
        //     $this->loadIfExists($file, $loader);
        // }
    }
}
