<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Filters\PaymentGatewaysRegistration;
use MultiSafepay\WooCommerce\Services\PaymentMethodService;

class Test_PaymentGatewaysRegistration extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_merges_multisafepay_gateways_with_existing_gateways() {
        $payment_method_service = $this->getMockBuilder( PaymentMethodService::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_woocommerce_payment_gateways' ) )
            ->getMock();

        $payment_method_service->method( 'get_woocommerce_payment_gateways' )
            ->willReturn(
                array(
                    'multisafepay_ideal' => 'ideal-gateway',
                )
            );

        $filter = new PaymentGatewaysRegistration( $payment_method_service );

        $result = $filter->get_woocommerce_payment_gateways( array( 'cod' ) );

        $this->assertArrayHasKey( 'multisafepay_ideal', $result );
        $this->assertContains( 'cod', $result );
        $this->assertEquals( 'ideal-gateway', $result['multisafepay_ideal'] );
    }
}
