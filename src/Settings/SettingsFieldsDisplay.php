<?php

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

namespace MultiSafepay\WooCommerce\Settings;

/**
 * The display fields settings fields
 *
 * Contains all the functions needed to display each setting field
 *
 * @since   4.0.0
 * @todo Remove html and use sprinfs
 */
class SettingsFieldsDisplay {

    /**
     * The ID of this plugin.
     *
     * @var      string    $plugin_name
     */
    private $plugin_name;

    /**
     * The field
     *
     * @var      array    $field
     */
    private $field;

    /**
     * Constructor the the class
     *
     * @var      string    $plugin_name
     * @var      array     $field
     */
    public function __construct(string $plugin_name, array $field) {
        $this->plugin_name = $plugin_name;
        $this->field = $field;
    }

    /**
     * Get the value by setting field
     *
     * @see https://developer.wordpress.org/reference/functions/get_option/
     * @param array $field
     */
    private function get_option_by_field( array $field ) {
        $value = get_option( $field['id'] );
        if($value) {
            return $value;
        }
        return $field['default'];
    }

    /**
     * Render the html for a text type input
     *
     * @param array $field
     * @return string
     */
    private function render_text_field( array $field ): string {
        $value = $this->get_option_by_field($field);
        $html = '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $value . '"/>';
        if(!empty($field['description'])) {
            $html .= '<p class="description">' . $field['description'] . '</p>';
        }
        return $html;
    }

    /**
     * Render the html for a select type input
     *
     * @param array $field
     * @return string
     */
    private function render_select_field( array $field ): string {
        $value = $this->get_option_by_field($field);
        $html = '<select name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '">';
        foreach( $field['options'] as $option_value => $option_name ) {
            if( $option_value == $value ) {
                $html .= '<option value="' . esc_attr( $option_value ) . '" selected>' . $option_name . '</option>';
            }
            if( $option_value != $value ) {
                $html .= '<option value="' . esc_attr( $option_value ) . '">' . $option_name . '</option>';
            }
        }
        $html .= '</select> ';
        if(!empty($field['description'])) {
            $html .= '<p class="description">' . $field['description'] . '</p>';
        }
        return $html;
    }

    /**
     * Render the html for each type of the registered setting field
     *
     * @param array $field
     * @return string
     */
    public function display() {
        $html           = '';
        switch( $this->field['type'] ) {
            case 'text':
                $html .= $this->render_text_field($this->field);
                break;
            case 'select':
                $html .= $this->render_select_field($this->field);
                break;
        }
        echo $html;
    }

}