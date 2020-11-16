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
?>

<?php if( $this->description ) { ?>
    <p><?php echo $this->description; ?></p>
<?php } ?>

<?php if( $issuers ) { ?>
    <p class="form-row form-row-wide validate-required" id="multisafepay_<?php echo $this->id ?>_issuer_id_field">
        <label for="multisafepay_<?php echo $this->id ?>_issuer_id" class=""><?php echo __('Issuer', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
        <span class="woocommerce-input-wrapper">
            <select name="issuer_id" id="multisafepay_<?php echo $this->id ?>_issuer_id">
                <option value=""><?php echo __( 'Select an issuer', 'multisafepay'); ?></option>
            </select>
        </span>
    </p>
<?php } ?>

<?php if($this->checkout_fields_ids) { ?>
    <?php if( in_array('gender', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide validate-required" id="multisafepay_<?php echo $this->id ?>_gender_field">
            <label for="multisafepay_<?php echo $this->id ?>_gender" class=""><?php echo __('Treatment', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
            <span class="woocommerce-input-wrapper">
                <select name="multisafepay_<?php echo $this->id ?>_gender" id="multisafepay_<?php echo $this->id ?>_gender">
                    <option value=""><?php echo __( 'Select an option', 'multisafepay'); ?></option>
                    <option value="male"><?php echo __( 'Mr', 'multisafepay'); ?></option>
                    <option value="female"><?php echo __( 'Mrs', 'multisafepay'); ?></option>
                    <option value="female"><?php echo __( 'Miss', 'multisafepay'); ?></option>
                </select>
            </span>
        </p>
    <?php } ?>
    <?php if( in_array('sex', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide validate-required" id="multisafepay_<?php echo $this->id ?>_gender_field">
            <label for="multisafepay_<?php echo $this->id ?>_gender" class=""><?php echo __('Gender', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
            <span class="woocommerce-input-wrapper">
                <select name="multisafepay_<?php echo $this->id ?>_gender" id="multisafepay_<?php echo $this->id ?>_gender">
                    <option value=""><?php echo __( 'Select an option', 'multisafepay'); ?></option>
                    <option value="male"><?php echo __( 'Male', 'multisafepay'); ?></option>
                    <option value="female"><?php echo __( 'Female', 'multisafepay'); ?></option>
                </select>
            </span>
        </p>
    <?php } ?>
    <?php if( in_array('birthday', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide validate-required" id="multisafepay_<?php echo $this->id ?>_birthday_field">
            <label for="multisafepay_<?php echo $this->id ?>_birthday" class=""><?php echo __('Date of birth', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
            <span class="woocommerce-input-wrapper">
                <input type="date" class="input-text" name="multisafepay_<?php echo $this->id ?>_birthday" id="multisafepay_<?php echo $this->id ?>_birthday" placeholder="dd-mm-yyyy"/>
            </span>
        </p>
    <?php } ?>
    <?php if( in_array('bank_account', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide validate-required" id="multisafepay_<?php echo $this->id ?>_bank_account_field">
            <label for="multisafepay_<?php echo $this->id ?>_bank_account_field" class=""><?php echo __('Bank Account', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
            <span class="woocommerce-input-wrapper">
                <input type="text" class="input-text" name="multisafepay_<?php echo $this->id ?>_bank_account" id="multisafepay_<?php echo $this->id ?>_bank_account_field" placeholder=""/>
            </span>
        </p>
    <?php } ?>
    <?php if( in_array('account_holder_name', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide validate-required" id="multisafepay_<?php echo $this->id ?>_account_holder_name_field">
            <label for="multisafepay_<?php echo $this->id ?>_account_holder_name" class=""><?php echo __('Account Holder Name', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
            <span class="woocommerce-input-wrapper">
                <input type="text" class="input-text" name="multisafepay_<?php echo $this->id ?>_account_holder_name" id="multisafepay_<?php echo $this->id ?>_account_holder_name" placeholder=""/>
            </span>
        </p>
    <?php } ?>
    <?php if( in_array('account_holder_iban', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide validate-required" id="multisafeay_<?php echo $this->id ?>_account_holder_iban_field">
            <label for="multisafeay_<?php echo $this->id ?>_account_holder_iban" class=""><?php echo __('Account IBAN', 'multisafepay'); ?><abbr class="required" title="required">*</abbr></label>
            <span class="woocommerce-input-wrapper">
                <input type="text" class="input-text" name="multisafeay_<?php echo $this->id ?>_account_holder_iban" id="multisafeay_<?php echo $this->id ?>_account_holder_iban" placeholder=""/>
            </span>
        </p>
    <?php } ?>
    <?php if( in_array('emandate', $this->checkout_fields_ids ) ) { ?>
        <p class="form-row form-row-wide" id="multisafepay_<?php echo $this->id ?>_emandate_field" style="display: none">
            <label for="multisafepay_<?php echo $this->id ?>_emandate" class=""><?php echo __('Emandate', 'multisafepay'); ?><span class="optional"><?php echo __('(optional)', 'multisafepay'); ?></span></label>
            <span class="woocommerce-input-wrapper">
                <input type="hidden" name="multisafeay_<?php echo $this->id ?>_emandate" id="multisafepay_<?php echo $this->id ?>_emandate" value="1" />
            </span>
        </p>
    <?php } ?>
<?php } ?>