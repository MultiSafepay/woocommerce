<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Services\Blocks\BlocksContextService;

/**
 * Covers Store API detection for Blocks requests.
 *
 * @covers \MultiSafepay\WooCommerce\Services\Blocks\BlocksContextService
 */
class Test_BlocksContextService extends WP_UnitTestCase {

    /**
     * @var bool
     */
    private $had_rest_route;

    /**
     * @var string
     */
    private $original_rest_route;

    /**
     * @var bool
     */
    private $had_request_uri;

    /**
     * @var string
     */
    private $original_request_uri;

    /**
     * @var bool
     */
    private $had_wp;

    /**
     * @var mixed
     */
    private $original_wp;

    /**
     * @return void
     */
    public function set_up() {
        parent::set_up();

        $this->had_rest_route     = isset( $_GET['rest_route'] );
        $this->original_rest_route = $this->had_rest_route ? (string) $_GET['rest_route'] : '';
        $this->had_request_uri    = isset( $_SERVER['REQUEST_URI'] );
        $this->original_request_uri = $this->had_request_uri ? (string) $_SERVER['REQUEST_URI'] : '';
        $this->had_wp             = isset( $GLOBALS['wp'] );
        $this->original_wp        = $this->had_wp ? $GLOBALS['wp'] : null;

        unset( $_GET['rest_route'] );
        $_SERVER['REQUEST_URI'] = '/checkout/';
        unset( $GLOBALS['wp'] );
    }

    /**
     * @return void
     */
    public function tear_down() {
        if ( $this->had_rest_route ) {
            $_GET['rest_route'] = $this->original_rest_route;
        } else {
            unset( $_GET['rest_route'] );
        }

        if ( $this->had_request_uri ) {
            $_SERVER['REQUEST_URI'] = $this->original_request_uri;
        } else {
            unset( $_SERVER['REQUEST_URI'] );
        }

        if ( $this->had_wp ) {
            $GLOBALS['wp'] = $this->original_wp;
        } else {
            unset( $GLOBALS['wp'] );
        }

        parent::tear_down();
    }

    /**
     * Falls back to the rest_route query parameter when WooCommerce's native detector returns false.
     *
     * @covers \MultiSafepay\WooCommerce\Services\Blocks\BlocksContextService::is_store_api_request
     * @return void
     */
    public function test_is_store_api_request_uses_rest_route_query_param_when_native_detector_is_false(): void {
        $_GET['rest_route']    = '/wc/store/v1/checkout';
        $_SERVER['REQUEST_URI'] = '/index.php?rest_route=/wc/store/v1/checkout';

        $service = new BlocksContextService();

        $this->assertTrue( $service->is_store_api_request() );
    }

    /**
     * Uses the parsed WP rest_route query var used by WooCommerce Store API internals.
     *
     * @covers \MultiSafepay\WooCommerce\Services\Blocks\BlocksContextService::is_store_api_request
     * @return void
     */
    public function test_is_store_api_request_uses_wp_query_var_rest_route(): void {
        $GLOBALS['wp'] = (object) array(
            'query_vars' => array(
                'rest_route' => '/wc/store/v1/checkout',
            ),
        );

        $service = new BlocksContextService();

        $this->assertTrue( $service->is_store_api_request() );
    }
}
