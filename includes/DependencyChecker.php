<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce;

use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

class DependencyChecker
{
    const REQUIRED_PLUGINS = [
        'WooCommerce' => 'woocommerce/woocommerce.php',
    ];

    /**
     * Check if there is a dependency missing to make the plugin work
     *
     * @throws MissingDependencyException
     * @return void
     */
    public function check(): void
    {
        $missingPlugins = $this->getMissingPluginsList();
        if (!empty($missingPlugins)) {
            throw new MissingDependencyException($missingPlugins);
        }
    }

    /**
     * Return the keys of all missing plugins
     *
     * @return array
     */
    private function getMissingPluginsList()
    {
        return array_keys(array_filter(self::REQUIRED_PLUGINS, [$this, 'isPluginInactive']));
    }

    /**
     * Check if a certain plugin is inactive
     *
     * @param string $pluginPath
     * @return boolean
     */
    private function isPluginInactive(string $pluginPath)
    {
        return !in_array($pluginPath, apply_filters('active_plugins', get_option('active_plugins')), true);
    }
}
