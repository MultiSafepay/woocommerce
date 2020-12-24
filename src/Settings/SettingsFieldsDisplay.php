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
 */

namespace MultiSafepay\WooCommerce\Settings;

/**
 * The display fields settings fields
 *
 * Contains all the functions needed to display each setting field
 *
 * @since   4.0.0
 */
class SettingsFieldsDisplay {

    /**
     * The ID of this plugin.
     *
     * @var      string
     */
    private $plugin_name;

    /**
     * The field
     *
     * @var      array
     */
    private $field;

    /**
     * Constructor the the class
     *
     * @param      string $plugin_name
     * @param      array  $field
     */
    public function __construct( string $plugin_name, array $field ) {
        $this->plugin_name = $plugin_name;
        $this->field       = $field;
    }

    /**
     * Get the value by setting field
     *
     * @see https://developer.wordpress.org/reference/functions/get_option/
     * @param   array $field
     * @return  mixed
     */
    private function get_option_by_field( array $field ) {
        $value = get_option( $field['id'], false );
        if ( ! $value ) {
            return $field['default'];
        }
        return $value;
    }

    /**
     * Render the html for a text type input
     *
     * @param array $field
     * @return string
     */
    private function render_text_field( array $field ): string {
        $value       = $this->get_option_by_field( $field );
        $field_id    = esc_attr( $field['id'] );
        $placeholder = esc_attr( $field['placeholder'] );
        $html        = '<input id="' . $field_id . '" type="' . $field['type'] . '" name="' . $field_id . '" placeholder="' . $placeholder . '" value="' . $value . '"/>';
        if ( ! empty( $field['description'] ) ) {
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
        $value    = $this->get_option_by_field( $field );
        $field_id = esc_attr( $field['id'] );
        $html     = '<select name="' . $field_id . '" id="' . $field_id . '">';
        foreach ( $field['options'] as $option_value => $option_name ) {
            if ( $option_value === $value ) {
                $html .= '<option value="' . esc_attr( $option_value ) . '" selected>' . $option_name . '</option>';
            }
            if ( $option_value !== $value ) {
                $html .= '<option value="' . esc_attr( $option_value ) . '">' . $option_name . '</option>';
            }
        }
        $html .= '</select> ';
        if ( ! empty( $field['description'] ) ) {
            $html .= '<p class="description">' . $field['description'] . '</p>';
        }
        return $html;
    }

    /**
     * Render the html for a checkbox type input
     *
     * @param array $field
     * @return string
     */
    private function render_checkbox_field( array $field ): string {
        $checked     = ( $this->get_option_by_field( $field ) ) ? 'checked="checked"' : '';
        $field_id    = esc_attr( $field['id'] );
        $placeholder = esc_attr( $field['placeholder'] );
        $html        = '<input id="' . $field_id . '" type="' . $field['type'] . '" name="' . $field_id . '" placeholder="' . $placeholder . '" value="1" ' . $checked . ' />';
        if ( ! empty( $field['description'] ) ) {
            $html .= '<p class="description">' . $field['description'] . '</p>';
        }
        return $html;
    }

    /**
     * Render the html for each type of the registered setting field
     *
     * @return void
     */
    public function display(): void {
        $html = '';
        switch ( $this->field['type'] ) {
            case 'text':
                $html .= $this->render_text_field( $this->field );
                break;
            case 'select':
                $html .= $this->render_select_field( $this->field );
                break;
            case 'checkbox':
                $html .= $this->render_checkbox_field( $this->field );
                break;
        }
        echo $html; // phpcs:ignore Standard.Category.SniffName.ErrorCode, WordPress.Security.EscapeOutput.OutputNotEscaped
    }

}
