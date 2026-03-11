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

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting }            = window.wc.wcSettings;

function check_apple_pay_availability() {
    return window.ApplePaySession && ApplePaySession.canMakePayments();
}

const createOptions     = ( gateway ) => {
    const labelElements = [];

    if ( gateway.icon ) {
        labelElements.push(
            React.createElement(
                'img',
                {
                    src: gateway.icon,
                    alt: gateway.title,
                    style: { height: '24px', width: 'auto', marginRight: '8px' }
                }
            )
        );
    }

    labelElements.push( gateway.title );

    const defaultSupports = ['products', 'refunds'];
    const gatewaySupports = gateway.supports || defaultSupports;

    return {
        name: gateway.id,
        label: React.createElement(
            'span',
            { style: { display: 'flex', alignItems: 'center' } },
            ...labelElements
        ),
    paymentMethodId: gateway.id,
    edit: React.createElement( 'div', null, '' ),
    canMakePayment: () => true,
    ariaLabel: gateway.title,
    content: React.createElement( 'div', null, gateway.description ),
    supports: {
        features: gatewaySupports,
        },
        placeOrderButtonLabel: undefined,
    };
};

if ( typeof window.multisafepay_gateways !== 'undefined' && Array.isArray( window.multisafepay_gateways ) ) {
    window.multisafepay_gateways.forEach(
        ( gateway ) => {
            if ( gateway.is_admin || ( gateway.id !== 'multisafepay_applepay' ) || check_apple_pay_availability() ) {
                registerPaymentMethod( createOptions( gateway ) );
            }
        }
    );
}
