<?php declare(strict_types=1);

namespace MultiSafepay\WooCommerce\Utils;

/**
 * Class EscapeUtil
 *
 * @package MultiSafepay\WooCommerce\Utils
 * @since    4.0.0
 */
class EscapeUtil {

    /**
     * Return an array with the allowed html tags to escape the output in the setting form
     *
     * @return array
     */
    public static function get_allowed_html_tags(): array {
        return array(
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
    }

}
