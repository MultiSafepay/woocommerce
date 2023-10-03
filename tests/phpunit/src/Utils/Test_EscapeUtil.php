<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\EscapeUtil;

class Test_EscapeUtil extends WP_UnitTestCase {

    public function test_escape_util() {
        $expected_allowed_tags = array(
            'input'  => array(
                'name'        => array(),
                'id'          => array(),
                'type'        => array(),
                'placeholder' => array(),
                'value'       => array(),
                'checked'     => true,
            ),
            'p'      => array(
                'class' => array(),
            ),
            'select' => array(
                'name' => array(),
                'id'   => array(),
            ),
            'option' => array(
                'value'    => array(),
                'selected' => true,
            ),
        );
        $allowed_tags = EscapeUtil::get_allowed_html_tags();
        $this->assertEquals($expected_allowed_tags, $allowed_tags);
    }
}
