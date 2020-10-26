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

/**
 * codingStandardsIgnoreFile
 */
namespace MultiSafepay\WooCommerce\PaymentMethods;

interface PaymentMethodInterface {

    /**
     * Set the ID of the payment method
     *
     * @param string $value
     * @return mixed
     */
    public function setId(string $value);

    /**
     * Set the title that is shown in the backend
     *
     * @param string $value
     * @return mixed
     */
    public function setMethodTitle(string $value);

    /**
     * How the payment should be handled by MultiSafepay
     *
     * @param $orderId
     * @return mixed
     */
    public function process_payment($orderId);

    /**
     * Add extra settings to a gateway
     *
     * @return mixed
     */
    public function addFormFields();

    /**
     * Set the method description in the backend
     *
     * @param string $value
     * @return mixed
     */
    public function setMethodDescription(string $value = '');
}
