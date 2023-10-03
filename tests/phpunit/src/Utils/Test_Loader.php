<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\Loader;

class Test_Loader extends WP_UnitTestCase {

    public function test_loader_filter() {
        $object = new stdClass();
        $loader = new Loader();
        $loader->add_filter( 'filter_name', $object, 'filter_method' );
        $this->assertIsArray($loader->filters);
        $this->assertArrayHasKey('hook', $loader->filters[0]);
        $this->assertArrayHasKey('callback', $loader->filters[0]);
        $this->assertArrayHasKey('priority', $loader->filters[0]);
        $this->assertArrayHasKey('accepted_args', $loader->filters[0]);
    }

    public function test_loader_action() {
        $object = new stdClass();
        $loader = new Loader();
        $loader->add_action( 'action_name', $object, 'action_method' );
        $this->assertIsArray($loader->actions);
        $this->assertArrayHasKey('hook', $loader->actions[0]);
        $this->assertArrayHasKey('callback', $loader->actions[0]);
        $this->assertArrayHasKey('priority', $loader->actions[0]);
        $this->assertArrayHasKey('accepted_args', $loader->actions[0]);
    }
}
