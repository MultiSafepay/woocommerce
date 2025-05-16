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

// Check if the debugDirect function is available, if not define it
if ( typeof debugDirect !== 'function' ) {
    window.debugDirect = function( debugMessage, debugEnabled, loggingType = 'error' ) {
        const allowedTypeArray = ['log', 'info', 'warn', 'error', 'debug'];

        if ( ! allowedTypeArray.includes( loggingType ) ) {
            loggingType = 'log';
        }

        if ( debugMessage && debugEnabled ) {
            console[loggingType]( debugMessage );
        }
    };
}

/**
 * Class to validate the fields in the checkout form
 */
class FieldsValidator {
    /**
     * Record the fields that have already been processed
     *
     * @type {string[]}
     */
    processedFields = [];

    /**
     * Cache for field labels to avoid repeated DOM lookups
     *
     * @type {Object}
     */
    fieldLabelsCache = {};

    /**
     * Cache for validated postcodes to avoid redundant AJAX calls
     * Format: { 'prefix[billing/shipping]:country:postcode': boolean }
     *
     * @type {Object}
     */
    validatedPostcodesCache = {};

    /**
     * Flag to indicate if validation is currently in progress
     *
     * @type {boolean}
     */
    validationInProgress = false;

    /**
     * Constructor - setup event listeners for form submission
     */
    constructor() {
        // Set up a global event listener to prevent payment when validation is in progress
        this.setupPaymentBlockingWhileValidating();
    }

    /**
     * Check if an element is visible
     *
     * @param {HTMLElement} element - Element to check visibility
     * @returns {boolean} - Whether the element is visible
     */
    isElementVisible( element ) {
        if ( ! element ) {
            return false;
        }

        const style = window.getComputedStyle( element );
        return ( element.offsetParent !== null ) ||
            (
                ( style.position === 'fixed' ) &&
                ( style.display !== 'none' ) &&
                ( style.visibility !== 'hidden' )
            );
    }

    /**
     * Setup event listener to prevent payment when validation is in progress
     *
     * @returns {void}
     */
    setupPaymentBlockingWhileValidating() {
        document.addEventListener(
            'click',
            ( event ) => {
                // If validation is in progress
                if ( this.validationInProgress ) {
                    // Look for payment buttons or elements that may trigger payment
                    const targetElement   = event.target;
                    const paymentTriggers = [
                        '.payment_method_multisafepay_applepay',
                        '.payment_method_multisafepay_googlepay',
                        '#place_order',
                        '.apple-pay-button',
                        '.google-pay-button'
                    ];

                    // Check if the clicked element matches any payment trigger selectors
                    const isPaymentTrigger = paymentTriggers.some(
                        selector =>
                            targetElement.matches && (
                                targetElement.matches( selector ) ||
                                targetElement.closest( selector )
                            )
                    );

                    if ( isPaymentTrigger ) {
                        debugDirect( 'Payment blocked: Validation in progress', debugStatus, 'log' );
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                }
            },
            true
        );
    }

    /**
     * Get the label text for a field
     *
     * @param fieldName - The name of the field
     * @returns {string}
     */
    getLabelText( fieldName ) {
        // If we already have the label cached, return it
        if ( this.fieldLabelsCache[fieldName] ) {
            return this.fieldLabelsCache[fieldName];
        }

        // Get the field element including input, text-areas and selects
        const fieldElement = document.querySelector(
            'input[name="' + fieldName + '"],' +
            'select[name="' + fieldName + '"],' +
            'textarea[name="' + fieldName + '"]'
        );

        if ( ! fieldElement ) {
            debugDirect( 'Field with name ' + fieldName + ' not found', debugStatus );
            return '';
        }

        const labelElement = fieldElement.closest( '.form-row' ).querySelector( 'label' );
        if ( ! labelElement ) {
            debugDirect( 'Label for field ' + fieldName + ' not found', debugStatus );
            return '';
        }

        // Check if the field is a billing or shipping field
        let prefix = '';
        if ( fieldName.startsWith( 'billing_' ) ) {
            prefix = 'Billing ';
        } else if ( fieldName.startsWith( 'shipping_' ) ) {
            prefix = 'Shipping ';
        }

        // Add the field to the array of processed fields
        this.processedFields.push( fieldName );

        // Create the label text including the prefix
        const labelText = prefix + labelElement.firstChild.textContent.trim();

        // Cache the label text
        this.fieldLabelsCache[fieldName] = labelText;

        // Return the label text
        return labelText;
    }

    /**
     * Validate an email address format
     *
     * @param {string} email - The email to validate
     * @returns {boolean} - Whether the email is valid
     */
    validateEmail( email ) {
        // Using the same logic as WooCommerce:
        // wp-content/plugins/woocommerce/assets/js/frontend/checkout.js
        const pattern = new RegExp( /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[0-9a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i );
        return pattern.test( email );
    }

    /**
     * Validate a postcode format via AJAX using WooCommerce's validation
     *
     * @param {string} postcode - The postcode to validate
     * @param {string} fieldName - The field name (to determine if billing or shipping)
     * @returns {Promise<boolean>} - Promise resolving to whether the postcode is valid
     */
    async validatePostcode( postcode, fieldName ) {
        // Signal that validation is in progress
        this.validationInProgress = true;

        // Extract country value based on whether this is billing or shipping
        const prefix       = fieldName.startsWith( 'billing_' ) ? 'billing' : 'shipping';
        const countryField = document.getElementById( prefix + '_country' );

        if ( ! countryField ) {
            debugDirect( 'Country field not found for ' + fieldName, debugStatus );
            this.validationInProgress = false;
            return true; // Default to valid if the country cannot be found
        }

        const country = countryField.value;

        // Generate a cache key using an address type, country and postcode
        const cacheKey = prefix + ':' + country + ':' + postcode;

        // Check if this postcode has already been validated for this country and address type
        if ( this.validatedPostcodesCache.hasOwnProperty( cacheKey ) ) {
            debugDirect( 'Using cached validation result for postcode: ' + postcode + ' in country: ' + country + ' for ' + prefix + ' address', debugStatus, 'log' );
            this.validationInProgress = false;
            return this.validatedPostcodesCache[cacheKey];
        }

        try {
            // Return a promise that resolves with a validation result
            return await new Promise(
                ( resolve ) => {
                    // Create data for AJAX request similar to WooCommerce's update_order_review
                    const data = {
                        action: 'multisafepay_validate_postcode',
                        security: multisafepayParams.nonce,
                        postcode: postcode,
                        country: country
                    };
                    debugDirect( 'Validating postcode via AJAX: ' + postcode + ' for country: ' + country + ' for ' + prefix + ' address', debugStatus, 'log' );
                    // Send AJAX request to validate postcode
                    jQuery.ajax(
                    {
                        type: 'POST',
                        url: multisafepayParams.location,
                        data: data,
                        success: function ( response ) {
                            let isValid = false;
                            if ( response.success ) {
                                isValid = true;
                            }
                            // Cache the validation result
                            this.validatedPostcodesCache[cacheKey] = isValid;

                            debugDirect( 'Postcode validation result via Ajax for ' + prefix + ' address: ' + postcode + ': ' + ( isValid ? 'valid' : 'invalid' ), debugStatus, 'log' );

                            // Signal that validation is complete
                            this.validationInProgress = false;

                            resolve( isValid );
                        }.bind( this ),
                        error: function () {
                            debugDirect( 'Error validating postcode via AJAX', debugStatus );
                            this.validationInProgress = false;
                            resolve( true ); // Default to valid on error
                        }.bind( this )
                    }
                    );
                }
            );
        } catch ( error ) {
            debugDirect( 'Exception validating postcode: ' + error, debugStatus );
            this.validationInProgress = false;
            return true; // Default to valid on error
        }
    }

    /**
     * Validate special fields like email and postcodes
     *
     * @param {HTMLElement} element - The element to be validated
     * @param labelText - The label text for the field
     * @returns {Promise<boolean>} - Whether the field is valid or not
     */
    async validateSpecialFields( element, labelText ) {
        const fieldName  = element.name.trim();
        const fieldValue = element.value.trim();

        // Get the field container element
        const getFieldId = document.getElementById( fieldName + '_field' );

        let isValid      = true;
        let errorMessage = '';

        // Check if this is one of the fields we need to specifically validate
        if ( fieldName === 'billing_email' ) {
            // Validate email format
            if ( ! this.validateEmail( fieldValue ) ) {
                isValid      = false;
                errorMessage = labelText + ' is not a valid address and ';
            }
        } else if ( fieldName === 'billing_postcode' || fieldName === 'shipping_postcode' ) {
            // Validate postcode via AJAX
            isValid = await this.validatePostcode( fieldValue, fieldName );
            if ( ! isValid ) {
                // If labelText is empty, get it from the cache or fall back to default
                if ( ! labelText || labelText.trim() === '' ) {
                    labelText = this.getLabelText( fieldName );

                    // If still empty, fallback to default
                    if ( ! labelText || labelText.trim() === '' ) {
                        const type = fieldName.startsWith( 'billing_' ) ? 'Billing' : 'Shipping';
                        labelText  = type + ' Postcode / ZIP';
                    }
                }
                errorMessage = labelText + ' is not valid for the selected country';
            }
        }

        // Apply validation results
        if ( isValid ) {
            this.removeErrorMessage( fieldName, true );
            this.removeInlineError( fieldName );
            element.style.cssText = '';
            ['aria-invalid', 'aria-describedby'].forEach( attr => element.removeAttribute( attr ) );
            getFieldId.classList.remove( 'woocommerce-invalid', 'woocommerce-invalid-required-field', 'woocommerce-invalid-email' );
            getFieldId.classList.add( 'woocommerce-validated' );
        } else {
            const errorId = fieldName + '_error';
            // For the top error banner - use the same message format
            this.appendErrorMessage( [{ field: fieldName, label: errorMessage }], true );

            // For inline errors, add a period at the end for postcode errors
            let inlineErrorMessage = errorMessage;
            if ( fieldName === 'billing_postcode' || fieldName === 'shipping_postcode' ) {
                inlineErrorMessage = errorMessage + '.';
            }

            this.addInlineError( fieldName, inlineErrorMessage, errorId );
            element.setAttribute( 'aria-invalid', 'true' );
            element.setAttribute( 'aria-describedby', errorId );
            element.style.cssText = 'box-shadow: inset 2px 0 0 #e2401c !important;';
            getFieldId.classList.remove( 'woocommerce-validated' );
            getFieldId.classList.add( 'woocommerce-invalid' );
            if ( fieldName.includes( 'email' ) ) {
                getFieldId.classList.add( 'woocommerce-invalid-email' );
            }
        }

        return isValid;
    }

    /**
     * Add an inline error message below a field
     *
     * @param {string} fieldName - The name of the field
     * @param {string} errorMessage - The error message
     * @param {string} errorId - The ID for the error element (for aria-describedby)
     * @returns {void}
     */
    addInlineError( fieldName, errorMessage, errorId ) {
        // Get the field element
        const fieldElement = document.querySelector(
            'input[name="' + fieldName + '"],' +
            'select[name="' + fieldName + '"],' +
            'textarea[name="' + fieldName + '"]'
        );

        if ( ! fieldElement ) {
            return;
        }

        // Remove any existing inline error
        this.removeInlineError( fieldName );

        // Create an inline error message
        const errorElement = document.createElement( 'p' );
        errorElement.setAttribute( 'id', errorId );
        errorElement.className   = 'checkout-inline-error-message';
        errorElement.textContent = errorMessage;

        // Add to a parent element
        const formRow = fieldElement.closest( '.form-row' );
        if ( formRow ) {
            formRow.appendChild( errorElement );
        }
    }

    /**
     * Remove inline error message
     *
     * @param {string} fieldName - The name of the field
     * @returns {void}
     */
    removeInlineError( fieldName ) {
        // Get the field element
        const fieldElement = document.querySelector(
            'input[name="' + fieldName + '"],' +
            'select[name="' + fieldName + '"],' +
            'textarea[name="' + fieldName + '"]'
        );

        if ( ! fieldElement ) {
            return;
        }

        // Remove any existing inline error
        const formRow = fieldElement.closest( '.form-row' );
        if ( formRow ) {
            const errorElement = formRow.querySelector( '.checkout-inline-error-message' );
            if ( errorElement ) {
                errorElement.remove();
            }
        }

        // Remove aria attributes
        ['aria-invalid', 'aria-describedby'].forEach( attr => fieldElement.removeAttribute( attr ) );
    }

    /**
     * Validate all the fields in real time
     *
     * @param element - The element to be validated
     * @returns {void}
     */
    realtimeValidation( element ) {
        const fieldName  = element.name.trim();
        const fieldValue = element.value.trim();

        // Remove this field from processedFields if it's there
        const index = this.processedFields.indexOf( fieldName );
        if ( index > -1 ) {
            this.processedFields.splice( index, 1 );
        }

        // Get the field container element
        const getFieldId = document.getElementById( fieldName + '_field' );
        const labelText  = this.getLabelText( fieldName );

        // If empty and required, show the required error
        if ( fieldValue === '' ) {
            if ( ( labelText !== '' ) && getFieldId && getFieldId.classList.contains( 'validate-required' ) ) {
                const errorId      = fieldName + '_error';
                const errorMessage = labelText + ' is a required field';
                this.appendErrorMessage( [{ field: fieldName, label: labelText }], true );
                this.addInlineError( fieldName, errorMessage, errorId );
                // Using the same error style as WooCommerce
                element.style.cssText = 'box-shadow: inset 2px 0 0 #e2401c !important;';
                element.setAttribute( 'aria-invalid', 'true' );
                element.setAttribute( 'aria-describedby', errorId );
                getFieldId.classList.remove( 'woocommerce-validated' );
                getFieldId.classList.add( 'woocommerce-invalid', 'woocommerce-invalid-required-field' );
            }
            return;
        }

        // For non-empty fields with specific validation needs, validate them
        if ( fieldName === 'billing_email' ) {
            this.validateSpecialFields( element, labelText ).catch(
                error => {
                    debugDirect( 'Error validating specific field: ' + error, debugStatus );
                }
            );
        } else {
            // For other fields that were filled in, remove any error messages
            this.removeErrorMessage( fieldName, true );
            this.removeInlineError( fieldName );
            element.style.cssText = '';
            getFieldId.classList.remove( 'woocommerce-invalid', 'woocommerce-invalid-required-field' );
            getFieldId.classList.add( 'woocommerce-validated' );
        }
    }

    /**
     * Setup validation listeners for postcode fields
     *
     * @param element - The element to setup listeners for
     * @returns {void}
     */
    setupPostcodeValidation( element ) {
        const fieldName = element.name.trim();

        // Get the label text for the field from our cache
        const labelText = this.getLabelText( fieldName );

        // Setup change event handler for postcodes
        element._changeHandler = () => {
            debugDirect( 'Validating postcode on change: ' + fieldName, debugStatus, 'log' );
            this.validateSpecialFields( element, labelText )
                .catch(
                    error => {
                        debugDirect( 'Error validating postcode: ' + error, debugStatus, 'error' );
                    }
                );
        };
        element.addEventListener( 'change', element._changeHandler );

        // Remove any previous focusout handler
        if ( element._focusoutHandler ) {
            element.removeEventListener( 'focusout', element._focusoutHandler );
            element._focusoutHandler = null;
        }
    }

    /**
     * Add event listeners to all required fields
     *
     * @returns {void}
     */
    setupValidationListeners() {
        // Getting the customer details element which includes all the user fields
        const customerDetails = document.getElementById( 'customer_details' );
        if ( ! customerDetails ) {
            return;
        }

        // Getting all the fields with the class 'validate-required'
        const selectWrappers = customerDetails.querySelectorAll( '.validate-required' );

        selectWrappers.forEach(
            wrapper => {
                // Getting all the fields inside the wrapper
                const elements          = wrapper.querySelectorAll( 'input, textarea, select' );
                elements.forEach(
                    element => {
                        const fieldName = element.name.trim();
                        // Remove any existing listeners first
                        element.removeEventListener( 'input', element._inputHandler );
                        element.removeEventListener( 'change', element._changeHandler );
                        element.removeEventListener( 'focusout', element._focusoutHandler );
                        // For postcodes, use only change event instead of input
                        if ( fieldName === 'billing_postcode' || fieldName === 'shipping_postcode' ) {
                            this.setupPostcodeValidation( element );
                        } else {
                            // For all other fields, use the input event for real-time validation
                            element._inputHandler = event => this.realtimeValidation( event.target );
                            element.addEventListener( 'input', element._inputHandler );

                            // Add focusout handler for all fields
                            element._focusoutHandler = event => this.realtimeValidation( event.target );
                            element.addEventListener( 'focusout', element._focusoutHandler );
                        }
                    }
                );
            }
        );
    }

    /**
     * Validate a specific postcode field
     *
     * @param {HTMLElement} postcodeElement - The postcode input element
     * @param {string} labelText - The label text for the field
     * @returns {Promise<boolean>} - Promise resolving to whether validation pass
     */
    async sharedPostcodeValidation( postcodeElement, labelText ) {
        const fieldName = postcodeElement.name.trim();

        // Start validation
        this.validationInProgress = true;

        // Validate the field
        const isValid = await this.validateSpecialFields( postcodeElement, labelText );

        // Update field styling if invalid
        if ( ! isValid ) {
            const postcodeField = document.getElementById( fieldName + '_field' );
            if ( postcodeField ) {
                postcodeField.classList.remove( 'woocommerce-validated' );
                postcodeField.classList.add( 'woocommerce-invalid' );
            }
        }

        return isValid;
    }

    /**
     * Validate all the fields with the class 'validate-required'
     * inside the element with the id 'customer_details'
     *
     * @returns {Promise<boolean>}
     */
    async checkFields() {
        // Early check - if validation is in progress, block payment
        if ( this.validationInProgress ) {
            debugDirect( 'Blocking payment: Validation in progress', debugStatus, 'log' );
            return false;
        }

        // Clear any existing errors from previous validations
        this.clearAllErrors();

        // Clear the processedFields array at the start of validation
        this.processedFields.length = 0;

        // Getting the customer details element which includes all the user fields
        const customerDetails = document.getElementById( 'customer_details' );
        if ( ! customerDetails ) {
            debugDirect( 'Customer details element not found', debugStatus, 'error' );
            return false;
        }

        // Are all fields valid? For now, yes
        let allFieldsValid = true;

        // STEP 1: Check all empty required fields first
        // Getting all the fields with the class 'validate-required'
        const requiredFields = customerDetails.querySelectorAll( '.validate-required' );
        debugDirect( 'Found ' + requiredFields.length + ' required fields', debugStatus, 'log' );

        // First pass: Validate empty required fields
        requiredFields.forEach(
            wrapper => {
                // Getting all the input elements inside the wrapper
                const elements      = wrapper.querySelectorAll( 'input, textarea, select' );
                elements.forEach(
                element => {
                    const fieldName = element.name.trim();
                    if ( ! fieldName ) {
                        return; // Skip elements without a name
                    }
                    // If the field is visible (or hidden) and is empty
                    if ( this.isElementVisible( element ) || element.type === 'hidden' ) {
                        if ( ! element.value.trim()) {
                            const labelText = this.getLabelText( fieldName );
                            if ( labelText ) {
                                const errorId      = fieldName + '_error';
                                const errorMessage = labelText + ' is a required field';

                                // Add an error message to the top of the page
                                this.appendErrorMessage( [{ field: fieldName, label: labelText }], true );

                                // Add inline error
                                this.addInlineError( fieldName, errorMessage, errorId );

                                // Invalid field styling
                                element.setAttribute( 'aria-invalid', 'true' );
                                element.setAttribute( 'aria-describedby', errorId );

                                // Add different error style depending on an element type
                                const select2Container = element.tagName.toLowerCase() === 'select' ?
                                wrapper.querySelector( '.select2-selection' ) : null;

                                if ( select2Container ) {
                                    select2Container.style.cssText = 'border: 2px solid #e2401c !important;';
                                } else {
                                    element.style.cssText = 'box-shadow: inset 2px 0 0 #e2401c !important;';
                                }

                                // Update wrapper classes
                                wrapper.classList.remove( 'woocommerce-validated' );
                                wrapper.classList.add( 'woocommerce-invalid', 'woocommerce-invalid-required-field' );

                                // Not all fields are valid
                                allFieldsValid = false;

                                debugDirect( 'Field is required but empty: ' + fieldName, debugStatus, 'log' );
                            }
                        }
                    }
                }
                );
            }
        );

        // STEP 2: Special validation for email and postcode
        // Setup validation listeners
        this.setupValidationListeners();

        // Explicitly check special fields (email and postcodes)
        const billingEmail     = document.querySelector( '[name="billing_email"]' );
        const billingPostcode  = document.querySelector( '[name="billing_postcode"]' );
        const shippingPostcode = document.querySelector( '[name="shipping_postcode"]' );

        // Flag to track if we need to wait for validations
        let pendingValidation = false;

        // Array to track validation promises
        const validationPromises = [];

        // Validate billing_email
        if ( billingEmail && this.isElementVisible( billingEmail ) && billingEmail.value.trim() !== '' ) {
            const labelText = this.getLabelText( 'billing_email' );

            // Check the email format synchronously
            if ( ! this.validateEmail( billingEmail.value.trim() )) {
                allFieldsValid          = false;
                const billingEmailField = document.getElementById( 'billing_email_field' );
                const errorId           = 'billing_email_error';
                const errorMessage      = labelText + ' is not a valid email address';

                // Add an error message to the top of the page
                this.appendErrorMessage( [{ field: 'billing_email', label: labelText + ' is not a valid email address and ' }], true );

                // Add inline error
                this.addInlineError( 'billing_email', errorMessage, errorId );
                billingEmail.setAttribute( 'aria-invalid', 'true' );
                billingEmail.setAttribute( 'aria-describedby', errorId );
                billingEmail.style.cssText = 'box-shadow: inset 2px 0 0 #e2401c !important;';

                if ( billingEmailField ) {
                    billingEmailField.classList.remove( 'woocommerce-validated' );
                    billingEmailField.classList.add( 'woocommerce-invalid', 'woocommerce-invalid-email' );
                }
            } else {
                // Email is valid, remove any error messages
                const billingEmailField = document.getElementById( 'billing_email_field' );
                this.removeErrorMessage( 'billing_email', true );
                this.removeInlineError( 'billing_email' );
                billingEmail.style.cssText = '';
                ['aria-invalid', 'aria-describedby'].forEach( attr => billingEmail.removeAttribute( attr ) );

                if ( billingEmailField ) {
                    billingEmailField.classList.remove( 'woocommerce-invalid', 'woocommerce-invalid-required-field', 'woocommerce-invalid-email' );
                    billingEmailField.classList.add( 'woocommerce-validated' );
                }
            }
        }

        // Check billing postcode
        if ( billingPostcode && this.isElementVisible( billingPostcode ) && billingPostcode.value.trim() !== '' ) {
            pendingValidation    = true;
            const labelText      = this.getLabelText( 'billing_postcode' );
            const billingPromise = this.sharedPostcodeValidation( billingPostcode, labelText )
                .then(
                    isValid => {
                        if ( ! isValid ) {
                            allFieldsValid = false;
                        }
                        return isValid;
                    }
                );
            validationPromises.push( billingPromise );
        }

        // Check shipping postcode
        if ( shippingPostcode && this.isElementVisible( shippingPostcode ) && shippingPostcode.value.trim() !== '' ) {
            pendingValidation     = true;
            const labelText       = this.getLabelText( 'shipping_postcode' );
            const shippingPromise = this.sharedPostcodeValidation( shippingPostcode, labelText )
                .then(
                    isValid => {
                        if ( ! isValid ) {
                            allFieldsValid = false;
                        }
                        return isValid;
                    }
                );
            validationPromises.push( shippingPromise );
        }

        // If there are pending validations, wait for them to complete
        if ( pendingValidation ) {
            debugDirect(
                'Waiting for ' + validationPromises.length + ' pending validation' +
                ( validationPromises.length === 1 ? '' : 's' ) + ' to complete',
                debugStatus,
                'log'
            );

            try {
                await Promise.all( validationPromises );
                debugDirect( 'All validations for special fields were completed', debugStatus, 'log' );
            } catch ( error ) {
                debugDirect( 'Error during special fields validation: ' + error, debugStatus, 'error' );
                allFieldsValid = false;
            } finally {
                this.validationInProgress = false;
            }
        }

        // Final check - ensure no validation is in progress
        if ( this.validationInProgress ) {
            debugDirect( 'Blocking payment: Validation still in progress at the end of checks', debugStatus, 'log' );
            return false;
        }

        // Scroll to the previously added notice group area if there are any errors
        if ( ! allFieldsValid ) {
            this.scrollToElement( '.entry-content' );
        }

        debugDirect( 'Final validation result: ' + ( allFieldsValid ? 'Valid' : 'Invalid' ), debugStatus, 'log' );
        return allFieldsValid;
    }

    /**
     * Clear all error messages and validation states
     *
     * @returns {void}
     */
    clearAllErrors() {
        // Remove the entire notice group
        const noticeGroup = document.querySelector( '.woocommerce-NoticeGroup-checkout' );
        if ( noticeGroup ) {
            noticeGroup.remove();
            debugDirect( 'Cleared all error messages', debugStatus, 'log' );
        }

        // Remove all inline errors
        const inlineErrors = document.querySelectorAll( '.checkout-inline-error-message' );
        inlineErrors.forEach( error => error.remove() );
    }

    /**
     * Scroll to a specific class element in the document.
     *
     * @param {string} className - The class name of the element to scroll to.
     * @returns {void}
     */
    scrollToElement( className ) {
        // Ensure the class name starts with a dot
        const selector = className.startsWith( '.' ) ? className : '.' + className;
        const element  = document.querySelector( selector );
        if ( element ) {
            element.scrollIntoView(
                {
                    behavior: 'smooth', // Optional: Adds a smooth scrolling effect
                    block: 'start'      // Optional: Aligns the element at the top of the view
                }
            );
        } else {
            debugDirect( 'Class name "' + className + '" not found', debugStatus, 'log' );
        }
    }

    /**
     * Append the error message to the checkout form
     *
     * @param {array} error - The error message to be added
     * @param {boolean} isRealtimeValidation - Whether the error message is added interactively or not
     * @returns {void}
     */
    appendErrorMessage( error, isRealtimeValidation = false ) {
        const formElement = document.querySelector( 'form[name="checkout"]' );
        if ( ! formElement ) {
            debugDirect( 'Checkout form not found', debugStatus );
            return;
        }

        // Make sure we have a valid error object
        if ( ! error || ! error[0] || ! error[0].field ) {
            debugDirect( 'Invalid error object passed to appendErrorMessage', debugStatus );
            return;
        }

        // Create the notice group using the WooCommerce style, where the errors will be built using JS vanilla,
        // so PHPCS doesn't complain if HTML code is used because of the opening and closing of tags <>
        let noticeGroup = document.querySelector( '.woocommerce-NoticeGroup-checkout' );
        if ( ! noticeGroup ) {
            noticeGroup           = document.createElement( 'div' );
            noticeGroup.className = 'woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout';
            formElement.parentNode.insertBefore( noticeGroup, formElement );

            const noticeBanner     = document.createElement( 'ul' );
            noticeBanner.className = 'woocommerce-error';
            noticeBanner.setAttribute( 'role', 'alert' );
            noticeGroup.appendChild( noticeBanner );
        }

        const ulList = noticeGroup.querySelector( 'ul' );
        if ( ! ulList ) {
            debugDirect( 'Error banner not found', debugStatus );
            return;
        }

        // Check if an error message for this field already exists
        const existingErrorItem = ulList.querySelector( 'li[data-id="' + error[0].field + '"]' );
        if ( existingErrorItem ) {
            // If we're in real-time validation, update the existing error message
            if ( isRealtimeValidation ) {
                debugDirect( 'Updating existing error message for field: ' + error[0].field, debugStatus, 'log' );
                // Remove the existing error so we can add an updated one
                existingErrorItem.remove();
            } else {
                // If not in real-time validation, just leave the existing error
                return;
            }
        }

        // Add a new error message
        const errorItem = document.createElement( 'li' );
        errorItem.setAttribute( 'data-id', error[0].field );
        errorItem.style.display = 'block'; // Ensure it's visible

        // Create the complete error message with formatting
        const errorLink                = document.createElement( 'a' );
        errorLink.href                 = '#' + error[0].field;
        errorLink.style.textDecoration = 'none'; // Don't show underline on the text

        // Create a strong element for the field name/error
        const strongElement = document.createElement( 'strong' );

        // Set the text for the strong element based on the error
        let errorText = error[0].label;
        // Make sure we end with "and" if the error is about invalid format
        if ( errorText.includes( 'is not valid' ) && ! errorText.endsWith( ' and' ) ) {
            errorText += ' and';
        }
        strongElement.textContent = errorText;

        // Determine the complete error message text
        let fullErrorText;
        if ( errorText.includes( 'is not valid' ) || errorText.includes( 'is not a valid' ) ) {
            fullErrorText = ' is a required field.';
        } else {
            fullErrorText = ' is a required field.';
        }

        // Add the complete message to the link
        errorLink.appendChild( strongElement );
        errorLink.appendChild( document.createTextNode( fullErrorText ) );

        // Add the link containing the full message to the error item
        errorItem.appendChild( errorLink );

        // Add the error message to the notice group
        ulList.appendChild( errorItem );

        // Add a click handler to focus the field when the error is clicked
        errorLink.addEventListener(
            'click',
            ( event ) => {
                event.preventDefault();
                const fieldToFocus = document.querySelector( '[name="' + error[0].field + '"]' );
                if ( fieldToFocus ) {
                    fieldToFocus.focus();
                }
            }
        );

        // Make sure the notice group is visible and properly positioned
        noticeGroup.style.display = 'block';
        noticeGroup.style.margin  = '0 0 2em';

        debugDirect( ( isRealtimeValidation ? '"Interactively" a' : 'A' ) + 'dding error message in the notice group for field: ' + error[0].field, debugStatus, 'log' );
    }

    /**
     * Remove the error message from the checkout form
     *
     * @param {string} fieldName - The field name associated with the error to be removed
     * @param {boolean} isRealtimeValidation - Whether the error message is removed interactively or not
     * @returns {void}
     */
    removeErrorMessage( fieldName, isRealtimeValidation = false ) {
        const errorItem = document.querySelector(
            '.woocommerce-NoticeGroup-checkout ul li[data-id="' + fieldName + '"]'
        );
        if ( errorItem ) {
            errorItem.remove();
            debugDirect( ( isRealtimeValidation ? '"Interactively" r' : 'R' ) + 'emoving error message in the notice group for field: ' + fieldName, debugStatus, 'log' );
        }
        this.removeEntireNoticeGroup();
    }

    /**
     * Remove the orphan error messages from the checkout form
     *
     * @returns {void}
     */
    removeOrphanErrorMessages() {
        const errorItems = document.querySelectorAll( '.woocommerce-NoticeGroup-checkout ul li' );
        errorItems.forEach(
            errorItem => {
                // Getting the field name added in the notice group
                const fieldName = errorItem.getAttribute( 'data-id' );
                debugDirect( 'Looking for ' + fieldName + ' to remove from notice group', debugStatus, 'log' );
                // Getting the field name associated with the error message
                const fieldElement = document.querySelector( '[name="' + fieldName + '"]' );
                // Check if the fieldElement exists and is not hidden
                const isFieldVisible = fieldElement && this.isElementVisible( fieldElement );
                if ( ! isFieldVisible ) {
                    errorItem.remove();
                    debugDirect( 'Remove the error message because field ' + fieldName + ' does not exist anymore', debugStatus, 'log' );
                }
            }
        );
        // If there are no more error messages left, remove the entire notice group
        this.removeEntireNoticeGroup();
    }

    /**
     * Remove the entire the space where the error messages
     * are displayed if there are no more error messages left
     *
     * @returns {void}
     */
    removeEntireNoticeGroup() {
        // Check if there are no more error messages left
        const noticeGroup = document.querySelector( '.woocommerce-NoticeGroup-checkout' );
        if ( noticeGroup ) {
            // Count elements with data-id attribute within the group
            const errorItems = noticeGroup.querySelectorAll( 'li[data-id]' );
            if ( errorItems.length === 0 ) {
                noticeGroup.remove();
                debugDirect( 'Removing the entire notice group as there are no more error messages left', debugStatus, 'log' );
            }
        }
    }
}
