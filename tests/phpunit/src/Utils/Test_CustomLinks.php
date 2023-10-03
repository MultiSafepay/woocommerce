<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\CustomLinks;

class Test_CustomLinks extends WP_UnitTestCase {

    public function test_custom_links() {
        $custom_links = (new CustomLinks())->get_links([]);
        $this->assertIsArray($custom_links);
        $this->assertNotEmpty($custom_links);
    }
}
