<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Exceptions;

/**
 * Class MissingDependencyException
 *
 * @package MultiSafepay\WooCommerce\Exceptions
 */
class MissingDependencyException extends \Exception {

    /**
     * The missing required plugins names
     *
     * @var array
     */
    private $missing_plugin_names;

    /**
     * MissingDependencyException constructor.
     *
     * @param array $missing_plugins
     */
    public function __construct( array $missing_plugins ) {
        parent::__construct();
        $this->missing_plugin_names = $missing_plugins;
    }

    /**
     * Get the list of all missing plugins
     *
     * @return array
     */
    public function get_missing_plugin_names(): array {
        return $this->missing_plugin_names;
    }
}
