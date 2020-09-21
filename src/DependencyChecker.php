<?php

namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

class DependencyChecker
{
    const REQUIRED_PLUGINS = [
        'WooCommerce' => 'woocommerce/woocommerce.php',
    ];

    public function check(): void
    {
        $missingPlugins = $this->getMissingPluginsList();
        if (!empty($missingPlugins)) {
            throw new MissingDependencyException($missingPlugins);
        }
    }

    private function getMissingPluginsList()
    {
        return array_keys(array_filter(
            self::REQUIRED_PLUGINS,
            [$this, 'isPluginInactive']
        ));
    }

    private function isPluginInactive($pluginPath)
    {
        return !in_array($pluginPath, apply_filters('active_plugins', get_option('active_plugins')));
    }
}
