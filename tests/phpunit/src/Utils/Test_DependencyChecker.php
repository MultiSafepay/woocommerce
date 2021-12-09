<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\DependencyChecker;
use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;


class Test_DependencyChecker extends WP_UnitTestCase {


    public function setUp() {
        parent::setUp();
    }

    public function test_dependency_checker_missing_dependency_exception() {
        $this->expectException(MissingDependencyException::class);
        (new DependencyChecker())->check();
    }

}
