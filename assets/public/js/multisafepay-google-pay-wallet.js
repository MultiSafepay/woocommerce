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

/**
 * Set of global variables following the Google Pay API
 *
 * - baseRequest: Object
 * - tokenizationSpecification: Object
 * - allowedCardNetworks: Array
 * - allowedCardAuthMethods: Array
 * - baseCardPaymentMethod: Object
 * - cardPaymentMethod: Object
 * - paymentsClient: Object
 * - isReadyToPayRequest: Object
 *
 * They need to be created from the global scope
 */
const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0
};

const tokenizationSpecification = {
    type: 'PAYMENT_GATEWAY',
    parameters: {
        'gateway': 'multisafepay',
        'gatewayMerchantId': configGooglePay.gatewayMerchantId.toString()
    }
};

const allowedCardNetworks    = ['MASTERCARD', 'VISA'];
const allowedCardAuthMethods = ['CRYPTOGRAM_3DS', 'PAN_ONLY'];

const baseCardPaymentMethod = {
    type: 'CARD',
    parameters: {
        allowedAuthMethods: allowedCardAuthMethods,
        allowedCardNetworks: allowedCardNetworks
    }
};

const cardPaymentMethod = Object.assign(
    {tokenizationSpecification: tokenizationSpecification},
    baseCardPaymentMethod
);

let paymentsClient = false, isReadyToPayRequest = false;

/**
 * Create a default value for configGooglePay if Google Pay is
 * enabled as redirect automatically
 *
 * @package MultiSafepay Shared Class for Direct Payments
 */
if (typeof configGooglePay === 'undefined') {
    configGooglePay = { 'debugMode' : null };
}

(function ($) {
    $(
        function () {
            /**
             * Checking if the Google Pay API file has been loaded
             */
            if ( ! window.google || ! window.google.payments || ! window.google.payments.api ) {
                console.error( 'Error initializing Google Pay: Script not loaded' );
            } else {
                const googlePayConfigEnvironment          = configGooglePay.environment === 'LIVE' ? 'PRODUCTION' : 'TEST';
                paymentsClient                            = new google.payments.api.PaymentsClient( { environment: googlePayConfigEnvironment } );
                isReadyToPayRequest                       = Object.assign( {}, baseRequest );
                isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];
            }
        }
    );
})( jQuery );

/**
 * Class for Google Pay Direct
 */
class GooglePayDirect {
    /**
     * @returns {void}
     */
    constructor() {
        /**
         * Initialize the debug mode if is configured
         */
        this.initializeDebug();

        /**
         * Initialize the class
         *
         * @returns {Promise<void>}
         */
        this.init()
            .then(
                () => {
                    debugDirect( 'Google Pay Direct class initialized', this.debug, 'log' );
                }
            )
            .catch(
                error => {
                    console.error( 'Error initializing Google Pay Direct:', error );
                }
            );
    }

    /**
     * Initialize the debug mode if configGooglePay is defined
     * and the debugMode is enabled
     *
     * @returns {void}
     */
    initializeDebug() {
        this.debug = ( typeof configGooglePay !== 'undefined' ) &&
            ( typeof configGooglePay.debugMode !== 'undefined' ) &&
            ( configGooglePay.debugMode === true );
    }

    /**
     * Initialize the process calling to create the button
     *
     * @returns {Promise<void>}
     */
    async init()
    {
        try {
            await this.createGooglePayButton();
        } catch ( error ) {
            console.error( 'Error creating Google Pay button:', error );
        }
    }

    /**
     * Create the Google Pay button
     *
     * @returns {Promise<void>}
     */
    async createGooglePayButton()
    {
        // Check if previous buttons already exist and remove them
        cleanUpDirectButtons();

        if ( ! paymentsClient || ! paymentsClient.createButton ) {
            debugDirect( 'Error creating Google Pay button: Script not loaded rightly', this.debug );
            return;
        }

        const buttonContainer = document.getElementById( 'place_order' ).parentElement;
        if ( ! buttonContainer ) {
            debugDirect( 'Button container not found', this.debug );
            return;
        }

        // Features of the button
        const buttonTag = paymentsClient.createButton(
            {
                buttonType: 'plain',
                buttonColor: 'black',
                buttonSizeMode: 'fill',
                onClick: this.onGooglePaymentButtonClicked.bind( this )
            }
        );

        const isSafari = /^((?!chrome|android).)*safari/i.test( navigator.userAgent );
        const height   = isSafari ? '65px' : '64px';
        buttonContainer.style.setProperty( 'height', height, 'important' );

        // Append the button to the div
        buttonContainer.appendChild( buttonTag );
    }

    /**
     * Create the Google Pay payment data request
     *
     * Some variables from the global scope are launched from
     * the internal code of Prestashop
     *
     * @returns {object} paymentDataRequest
     */
    getGooglePaymentDataRequest()
    {
        const paymentDataRequest                 = Object.assign( {}, baseRequest );
        paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
        paymentDataRequest.transactionInfo       = {
            totalPriceStatus: 'FINAL',
            totalPrice: configGooglePay.totalPrice.toFixed( 2 ),
            currencyCode: configGooglePay.currencyCode,
            countryCode: configGooglePay.countryCode
        };
        paymentDataRequest.merchantInfo          = {
            merchantName: configGooglePay.merchantName,
            merchantId: configGooglePay.merchantId
        };
        return paymentDataRequest;
    }

    /**
     * Event handler for the Google Pay button
     *
     * @returns {Promise<void>}
     */
    async onGooglePaymentButtonClicked()
    {
        const validatorInstance = new FieldsValidator();
        const fieldsAreValid    = await validatorInstance.checkFields();
        if ( fieldsAreValid ) {
            if ( paymentsClient && paymentsClient.loadPaymentData ) {
                try {
                    const dataRequest = this.getGooglePaymentDataRequest();
                    if (this.debug && ( ! dataRequest || (typeof dataRequest !== 'object' ) ) ) {
                        debugDirect( 'Invalid data from paymentDataRequest object', this.debug );
                    }

                    const paymentData      = await paymentsClient.loadPaymentData( dataRequest );
                    const processedPayment = this.processGooglePayment( paymentData );
                    if ( this.debug && ! processedPayment ) {
                        debugDirect( 'Failed to process Google Pay payment', this.debug );
                    }
                } catch ( message ) {
                    console.error( 'Message from the Google Pay API:', message );
                }
            } else {
                debugDirect( 'Terms of Service for Google Pay not checked', this.debug, 'warn' );
            }
        } else {
            debugDirect( 'Not all mandatory fields were filled out', this.debug, 'warn' );
        }
    }

    /**
     * Submit the Google Pay form
     *
     * @param {string} paymentToken
     * @returns {boolean}
     */
    submitGooglePayForm( paymentToken )
    {
        if ( ( typeof paymentToken !== 'string' ) || ( paymentToken.trim() === '' ) ) {
            debugDirect( 'Invalid payload provided', this.debug );
            return false;
        }

        const googlepayForm = document.querySelector( 'form[name="checkout"]' );

        if ( ! googlepayForm ) {
            debugDirect( 'Google Pay form not found', this.debug );
            return false;
        }

        // Settings the features of the input field
        const inputField = document.createElement( 'input' );
        inputField.type  = 'hidden';
        inputField.name  = 'payment_token';
        inputField.value = paymentToken;

        // Settings the features of the browser field
        const browserField = document.createElement( 'input' );
        browserField.type  = 'hidden';
        browserField.name  = 'browser';
        browserField.value = getCustomerBrowserInfo();

        // Add the hidden field to the form including the token value
        googlepayForm.appendChild( inputField );
        // Add the hidden field to the form including the browser info
        googlepayForm.appendChild( browserField );
        // Submit the form automatically
        googlepayForm.dispatchEvent( new Event( 'submit' ) );
        return true;
    }

    /**
     * @param {object} paymentData
     * @returns {boolean}
     */
    processGooglePayment(paymentData)
    {
        // Validate input
        if ( ! paymentData ||
            ! paymentData.paymentMethodData ||
            ! paymentData.paymentMethodData.tokenizationData ||
            ! paymentData.paymentMethodData.tokenizationData.token
        ) {
            debugDirect( 'Invalid payment data received', this.debug );
            return false;
        }

        // Extract the token from the payment data sent by Google Pay
        const payload = paymentData.paymentMethodData.tokenizationData.token;

        // Check if the payload is a string and not empty
        if ( ( typeof payload !== 'string' ) || ( payload.trim() === '' ) ) {
            debugDirect( 'Invalid token received', this.debug );
            return false;
        }

        // Call the submit function only if the payload is valid
        return this.submitGooglePayForm( payload );
    }
}
