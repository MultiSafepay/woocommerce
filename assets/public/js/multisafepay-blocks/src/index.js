/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @package     MultiSafepay
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

function check_apple_pay_availability() {
    return window.ApplePaySession && ApplePaySession.canMakePayments();
}

const registerMultiSafepayPaymentMethods = ( { wc, multisafepay_gateways } ) => {
    const { registerPaymentMethod }      = wc.wcBlocksRegistry;

    multisafepay_gateways.forEach(
        ( gateway ) =>
        {
            if ( ( gateway.id !== 'multisafepay_applepay' ) || check_apple_pay_availability() ) {
                registerPaymentMethod( createOptions( gateway ) );
            }
        }
    );
}

const createOptions = ( gateway ) => {
    return {
        name: gateway.id,
        label: gateway.title,
        paymentMethodId: gateway.id,
        edit: React.createElement( 'div', null, '' ),
        canMakePayment: () => true,
        ariaLabel: gateway.title,
        content: React.createElement( 'div', null, gateway.description ),
    };
};

document.addEventListener(
    'DOMContentLoaded',
    () =>
    {
        registerMultiSafepayPaymentMethods(
            window
        );
    }
);
