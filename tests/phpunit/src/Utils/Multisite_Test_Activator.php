<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\DependencyChecker;
use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

class Multisite_Test_Activator extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
        $username = 'user-' . rand( 1, 2000 );
        $password = wp_generate_password(12, false);
        $email = $username . '@example.org';
        $user_id = wpmu_create_user( $username, $password, $email );
        wpmu_create_blog( 'example.org', "/blog-1/", 'Blog 1', $user_id , array( 'public' => 1 ) );
        $this->blogs_ids = get_sites( array( 'fields' => 'ids' ) );
    }

    public function test_dependency_checker_missing_dependency_exception() {
        $this->assertCount( 2, $this->blogs_ids );
        foreach ( $this->blogs_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            $this->assertTrue( true );
            restore_current_blog();
        }
    }

}
