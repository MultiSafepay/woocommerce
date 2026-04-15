<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\PaymentMethods\Base\BasePaymentMethod;
use MultiSafepay\WooCommerce\PaymentMethods\Filters\NonDuplicatedBrandedNames;

class Test_NonDuplicatedBrandedNames extends WP_UnitTestCase {

    /**
     * @return void
     */
    public function test_shortens_only_unique_branded_titles_in_frontend_context() {
        set_current_screen( 'front' );

        $filter = new NonDuplicatedBrandedNames();

        $single_gateway = $this->getMockBuilder( BasePaymentMethod::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_title' ) )
            ->getMock();
        $single_gateway->id = 'multisafepay_visa';
        $single_gateway->method( 'get_title' )->willReturn( 'Visa - Credit Cards' );

        $duplicate_gateway_one = $this->getMockBuilder( BasePaymentMethod::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_title' ) )
            ->getMock();
        $duplicate_gateway_one->id = 'multisafepay_ideal_shop';
        $duplicate_gateway_one->method( 'get_title' )->willReturn( 'iDEAL - Shop' );

        $duplicate_gateway_two = $this->getMockBuilder( BasePaymentMethod::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'get_title' ) )
            ->getMock();
        $duplicate_gateway_two->id = 'multisafepay_ideal_qr';
        $duplicate_gateway_two->method( 'get_title' )->willReturn( 'iDEAL - QR' );

        $result = $filter->filter_non_duplicated_branded_names(
            array(
                'single' => $single_gateway,
                'dup1'   => $duplicate_gateway_one,
                'dup2'   => $duplicate_gateway_two,
            )
        );

        $this->assertEquals( 'Visa', $result['single']->title );
        $this->assertEquals( 'iDEAL - Shop', $result['dup1']->title );
        $this->assertEquals( 'iDEAL - QR', $result['dup2']->title );
    }
}
