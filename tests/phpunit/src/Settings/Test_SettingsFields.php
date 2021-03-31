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

class Test_SettingsFields extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        $settings_fields = new SettingsFields();
        $this->settings_fields = $settings_fields->get_settings();
    }

    public function test_get_settings() {
        $this->assertIsArray( $this->settings_fields );
    }

    public function test_get_settings_has_general_in_array() {
        $this->assertArrayHasKey( 'general', $this->settings_fields );
    }

    public function test_get_settings_has_options_in_array() {
        $this->assertArrayHasKey( 'options', $this->settings_fields );
    }

    public function test_get_settings_has_order_status_in_array() {
        $this->assertArrayHasKey( 'order_status', $this->settings_fields );
    }

    public function test_each_section_contains_title_intro_and_fields() {
        foreach ( $this->settings_fields as $section ) {
            $this->assertArrayHasKey( 'title', $section );
            $this->assertArrayHasKey( 'intro', $section );
            $this->assertArrayHasKey( 'fields', $section );
            $this->assertIsArray( $section['fields'] );
        }
    }

    public function test_each_field_array_has_keys() {
        foreach ($this->settings_fields as $section) {
            foreach ($section['fields'] as $field) {
                $this->assertArrayHasKey( 'id', $field );
                $this->assertArrayHasKey( 'label', $field );
                $this->assertArrayHasKey( 'description', $field );
                $this->assertArrayHasKey( 'type', $field );
                $this->assertArrayHasKey( 'default', $field );
                $this->assertArrayHasKey( 'placeholder', $field );
                $this->assertArrayHasKey( 'tooltip', $field );
                $this->assertArrayHasKey( 'callback', $field );
                $this->assertArrayHasKey( 'setting_type', $field );
                $this->assertArrayHasKey( 'sort_order', $field );
            }
        }
    }

    public function test_each_field_array_key_is() {
        foreach ($this->settings_fields as $section) {
            foreach ($section['fields'] as $field) {
                $this->assertIsString( $field['id'] );
                $this->assertIsString( $field['label'] );
                $this->assertIsString( $field['description'] );
                $this->assertIsString( $field['type'] );
                $this->assertIsString( $field['placeholder'] );
                $this->assertIsString( $field['tooltip'] );
                if(!empty( $field['callback'] )) {
                    $this->assertIsArray( $field['callback'] );
                }
                if(empty( $field['callback'] )) {
                    $this->assertIsString( $field['callback'] );
                }
                $this->assertIsString( $field['setting_type'] );
                $this->assertIsInt( $field['sort_order'] );
            }
        }
    }

}
