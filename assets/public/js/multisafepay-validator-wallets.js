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
     * Get the label text for a field
     *
     * @param fieldName - The name of the field
     * @returns {string}
     */
    getLabelText( fieldName ) {
        // Skip if this field has already been processed
        if ( this.processedFields.indexOf( fieldName ) !== -1 ) {
            return '';
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

        // Return the label text including the prefix
        return prefix + labelElement.firstChild.textContent.trim();
    }

    /**
     * Validate all the fields in real time
     *
     * @param element - The element to be validated
     */
    realtimeValidation( element ) {
        const fieldName = element.name.trim();

        // Remove this field from processedFields if it's there
        const index = this.processedFields.indexOf( fieldName );
        if ( index > -1 ) {
            this.processedFields.splice( index, 1 );
        }

        if ( element.value.trim() !== '' ) {
            this.removeErrorMessage( fieldName, true );
            element.style.cssText = '';
        } else {
            const labelText = this.getLabelText( fieldName );
            // Any field name has an associated ID with the suffix '_field'
            const getFieldId = document.getElementById( fieldName + '_field' );
            // If the label text is not empty and field is required
            if ( (labelText !== '') && getFieldId.classList.contains( 'validate-required' ) ) {
                this.appendErrorMessage( [{ field: fieldName, label: labelText }], true );
            }
            // Using the same error style as WooCommerce
            element.style.cssText = 'box-shadow: inset 2px 0 0 #e2401c !important;';
        }
    }

    /**
     * Validate all the fields with the class 'validate-required'
     * inside the element with the id 'customer_details'
     *
     * @returns {boolean}
     */
    checkFields() {
        // Clear the processedFields array at the start of validation
        this.processedFields.length = 0;

        // Getting the customer details element which includes all the user fields
        const customerDetails = document.getElementById( 'customer_details' );
        // Getting all the fields with the class 'validate-required' so we can loop through them
        const selectWrappers = customerDetails.querySelectorAll( '.validate-required' );
        // Check if the element is visible
        const isElementVisible = element => element && element.offsetParent !== null;
        // Are all fields valid? For now, yes
        let allFieldsValid = true;

        // Loop through all the fields with the class 'validate-required'
        selectWrappers.forEach(
            wrapper => {
                // Getting all the fields inside the wrapper including input, text-areas and selects
                const elements = wrapper.querySelectorAll( 'input, textarea, select' );
                // Loop through all the fields inside the wrapper
                elements.forEach(
                    element => {
                        // Remove existing listener and add a new one
                        element.removeEventListener( 'input', event => this.realtimeValidation( event.target ) );
                        element.addEventListener( 'input', event => this.realtimeValidation( event.target ) );
                        const fieldName = element.name.trim();
                        // Initial validation logic: if the field is empty and visible
                        if ( ! element.value.trim() && ( isElementVisible( element ) || element.type === 'hidden' ) ) {
                            const labelText = this.getLabelText( fieldName );
                            if ( labelText !== '' ) {
                                this.appendErrorMessage( [{ field: fieldName, label: labelText }] );
                            }
                            // Not all fields are valid
                            allFieldsValid = false;
                            // Add a different error style to the field if it's a Select2 or input,
                            // using the same error style as WooCommerce
                            const select2Container = element.tagName.toLowerCase() === 'select' ? wrapper.querySelector( '.select2-selection' ) : null;
                            if ( select2Container ) {
                                select2Container.style.cssText = 'border: 2px solid #e2401c !important;';
                            } else {
                                element.style.cssText = 'box-shadow: inset 2px 0 0 #e2401c !important;';
                            }
                        } else {
                            this.removeErrorMessage( fieldName );
                            element.style.cssText = '';
                        }
                    }
                );
            }
        );

        // Scroll to the previously added notice group area if there are any errors
        if ( ! allFieldsValid ) {
            this.scrollToElement( '.entry-content' );
        }
        return allFieldsValid;
    }

    /**
     * Scroll to a specific class element in the document.
     *
     * @param {string} className - The class name of the element to scroll to.
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

        // Create the notice group using the WooCommerce style, where the errors will be built using JS vanilla,
        // so PHPCS doesn't complain if HTML code is used because the opening and closing of tags <>
        let noticeGroup = document.querySelector( '.woocommerce-NoticeGroup-checkout' );
        if ( ! noticeGroup ) {
            noticeGroup           = document.createElement( 'div' );
            noticeGroup.className = 'woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout';
            formElement.parentNode.insertBefore( noticeGroup, formElement );

            const noticeBanner     = document.createElement( 'ul' );
            noticeBanner.className = 'woocommerce-error';
            noticeBanner.setAttribute( 'role', 'alert' );

            const contentDiv     = document.createElement( 'div' );
            contentDiv.className = 'wc-block-components-notice-banner__content';

            const summaryParagraph       = document.createElement( 'p' );
            summaryParagraph.className   = 'wc-block-components-notice-banner__summary';
            summaryParagraph.textContent = 'The following problems were found:';
            contentDiv.appendChild( summaryParagraph );

            const ulElement = document.createElement( 'ul' );
            contentDiv.appendChild( ulElement );

            noticeBanner.appendChild( contentDiv );
            noticeGroup.appendChild( noticeBanner );
        }

        const ulList = noticeGroup.querySelector( 'ul' );

        // Check if an error message for this field already exists, therefore, is not added again
        const existingErrorItem = ulList.querySelector( 'li[data-id="' + error[0].field + '"]' );
        if ( existingErrorItem ) {
            return;
        }

        // Add a new error message
        const errorItem = document.createElement( 'li' );
        errorItem.setAttribute( 'data-id', error[0].field );

        const strongElement = document.createElement( 'strong' );
        // Add the label text to the error message
        strongElement.textContent = error[0].label;

        errorItem.appendChild( strongElement );
        errorItem.append( ' is a required field.' );
        // Add the error message to the notice group
        ulList.appendChild( errorItem );

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
                // Check if the fieldElement exists and is not hidden using CSS
                const isFieldVisible = fieldElement && ( window.getComputedStyle( fieldElement ).display !== 'none' );
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
        const errorList = document.querySelector( '.woocommerce-NoticeGroup-checkout ul' );
        if ( errorList && ( errorList.children.length === 0 ) ) {
            const noticeGroup = document.querySelector( '.woocommerce-NoticeGroup-checkout' );
            if ( noticeGroup ) {
                noticeGroup.remove();
                debugDirect( 'Removing the entire notice group as there are no more error messages left', debugStatus, 'log' );
            }
        }
    }
}
