<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Exceptions;


class MissingDependencyException extends \Exception
{
    private $missingPluginNames;

    /**
     * MissingDependencyException constructor.
     * @param array $missingPlugins
     */
    public function __construct(array $missingPlugins)
    {
        parent::__construct();
        $this->missingPluginNames = $missingPlugins;
    }

    /**
     * Get the list of all missing plugins
     *
     * @return array
     */
    public function getMissingPluginNames(): array
    {
        return $this->missingPluginNames;
    }

}
