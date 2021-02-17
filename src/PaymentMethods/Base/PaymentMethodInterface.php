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

namespace MultiSafepay\WooCommerce\PaymentMethods\Base;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;

/**
 * Interface PaymentMethodInterface
 *
 * @package MultiSafepay\WooCommerce\PaymentMethods
 */
interface PaymentMethodInterface {

    /**
     * Get the ID of the payment method
     *
     * @return string
     */
    public function get_payment_method_id(): string;

    /**
     * Get the code of the payment method
     *
     * @return string
     */
    public function get_payment_method_code(): string;

    /**
     * Get the method type, should be 'direct' or 'redirect'
     *
     * @return string
     */
    public function get_payment_method_type(): string;

    /**
     * Get the title that is shown in the backend
     *
     * @return string
     */
    public function get_payment_method_title(): string;

    /**
     * Get the method description in the backend
     *
     * @return string
     */
    public function get_payment_method_description(): string;

    /**
     * Add extra settings to a gateway
     *
     * @return array
     */
    public function add_form_fields();

    /**
     * Get has fields
     *
     * @return boolean
     */
    public function has_fields(): bool;

    /**
     * Add custom checkout fields by id
     *
     * @return array
     */
    public function get_checkout_fields_ids(): array;

    /**
     * Add icon to a gateway
     *
     * @return string
     */
    public function get_payment_method_icon(): string;

    /**
     * Add gatewayinfo to request
     *
     * @param array|null $data
     * @return GatewayInfoInterface
     */
    public function get_gateway_info( array $data = null): GatewayInfoInterface;

    /**
     * Check if the gateway info is complete, otherwise you can perform custom actions
     *
     * @param GatewayInfoInterface $gateway_info
     * @return boolean
     */
    public function validate_gateway_info( GatewayInfoInterface $gateway_info): bool;

}
