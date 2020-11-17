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

namespace MultiSafepay\WooCommerce\PaymentMethods;

/**
 * Define the Gateways.
 *
 * @since   4.0.0
 */
class Gateways {

    const GATEWAYS = array(
        'multisafepay'  => MultiSafepay::class,
        'ideal'         => Ideal::class,
        'einvocing'     => Einvocing::class,
        'afterpay'      => Afterpay::class,
        'dirdeb'        => Dirdeb::class,
        'dotpay'        => Dotpay::class,
        'santander'     => Santander::class
    );

    /**
     * Return an array with all MultiSafepay gateways ids
     *
     * @return array
     */
    public function get_gateways_ids(): array {
        $gateways_ids = array();
        foreach (self::GATEWAYS as $gateway_id => $gateway) {
            $gateways_ids[] = $gateway_id;
        }
        return $gateways_ids;
    }

}