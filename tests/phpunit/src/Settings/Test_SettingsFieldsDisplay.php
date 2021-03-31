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

use MultiSafepay\WooCommerce\Settings\SettingsFields;
use MultiSafepay\WooCommerce\Settings\SettingsFieldsDisplay;

class Test_SettingsFieldsDisplay extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        $settings_fields = new SettingsFields();
        $this->settings_fields = $settings_fields->get_settings();
    }

    public function test_display() {
        foreach ($this->settings_fields as $section) {
            foreach ($section['fields'] as $field) {
                $settings_fields_display = new SettingsFieldsDisplay( $field );
                ob_start();
                $settings_fields_display->display();
                $output = ob_get_clean();
                $this->assertRegExp( '/name="' . $field['id'] . '"/', $output );
                $this->assertRegExp( '/' . $field['type'] . '/', $output );
            }
        }
    }

}
