<?php

namespace MultiSafepay\WooCommerce\Exceptions;


class MissingDependencyException extends \Exception
{
    private $missingPluginNames;

    public function __construct($missingPlugins)
    {
        parent::__construct();
        $this->missingPluginNames = $missingPlugins;
    }

    public function getMissingPluginNames()
    {
        return $this->missingPluginNames;
    }

}
