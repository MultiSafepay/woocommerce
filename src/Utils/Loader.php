<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

/**
 * Register all the actions and filters for the plugin.
 */
class Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @var array The actions registered with WordPress to fire when the plugin loads.
     */
    public $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @var array The filters registered with WordPress to fire when the plugin loads.
     */
    public $filters;

    /**
     * Initialize the collections used to maintain the actions and filters.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param string $hook             The name of the WordPress action that is being registered.
     * @param object $component        A reference to the instance of the object on which the action is defined.
     * @param string $callback         The name of the function defined on the $component.
     * @param int    $priority         The priority at which the function should be fired.
     * @param int    $accepted_args    The number of arguments that should be passed to the $callback.
     * @return void
     */
    public function add_action( string $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param string $hook             The name of the WordPress filter that is being registered.
     * @param object $component        A reference to the instance of the object on which the filter is defined.
     * @param string $callback         The name of the function defined on the $component.
     * @param int    $priority         The priority at which the function should be fired.
     * @param int    $accepted_args    The number of arguments that should be passed to the $callback.
     * @return void
     */
    public function add_filter( string $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @param array  $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param string $hook             The name of the WordPress filter that is being registered.
     * @param object $component        A reference to the instance of the object on which the filter is defined.
     * @param string $callback         The name of the function definition on the $component.
     * @param int    $priority         The priority at which the function should be fired.
     * @param int    $accepted_args    The number of arguments that should be passed to the $callback.
     * @return array                          The collection of actions and filters registered with WordPress.
     */
    private function add( array $hooks, string $hook, $component, string $callback, int $priority, int $accepted_args ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @return void
     */
    public function init() {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }
    }
}
