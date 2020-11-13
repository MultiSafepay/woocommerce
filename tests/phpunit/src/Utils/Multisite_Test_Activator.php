<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

use MultiSafepay\WooCommerce\Utils\DependencyChecker;
use MultiSafepay\WooCommerce\Exceptions\MissingDependencyException;

class Multisite_Test_Activator extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
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
