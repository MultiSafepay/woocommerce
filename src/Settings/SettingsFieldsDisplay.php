<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Settings;

use MultiSafepay\WooCommerce\Utils\EscapeUtil;

/**
 * The display fields settings fields
 *
 * Contains all the functions needed to display each setting field
 */
class SettingsFieldsDisplay {

    /**
     * The field
     *
     * @var      array
     */
    private $field;

    /**
     * Constructor the class
     *
     * @param array $field
     */
    public function __construct( array $field ) {
        $this->field = $field;
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
        return esc_html( $value );
    }

    /**
     * Render the html for a text type input
     *
     * @param  array $field
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
     * Render the html for a password type input
     *
     * @param array $field
     * @return string
     */
    private function render_password_field( array $field ): string {
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
            case 'password':
                $html .= $this->render_password_field( $this->field );
                break;
        }

        echo wp_kses( $html, EscapeUtil::get_allowed_html_tags() );
    }

}
