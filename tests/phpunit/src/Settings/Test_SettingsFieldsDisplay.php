<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Settings\SettingsFields;
use MultiSafepay\WooCommerce\Settings\SettingsFieldsDisplay;

class Test_SettingsFieldsDisplay extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
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
