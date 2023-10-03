<?php declare(strict_types=1); ?>

<?php if ( $this->description ) { ?>
    <p><?php echo esc_html( $this->description ); ?></p>
<?php } ?>

<?php if ( $this->payment_component ) { ?>
    <div id="<?php echo esc_attr( $this->id ); ?>_payment_component_container" class="multisafepay-payment-component"></div>
    <p class="form-row form-row-wide" id="<?php echo esc_attr( $this->id ); ?>_payment_component_field" style="display: none">
        <span class="woocommerce-input-wrapper">
            <input type="hidden" name="<?php echo esc_attr( $this->id ); ?>_payment_component_payload" id="<?php echo esc_attr( $this->id ); ?>_payment_component_payload" />
            <input type="hidden" name="<?php echo esc_attr( $this->id ); ?>_payment_component_tokenize" id="<?php echo esc_attr( $this->id ); ?>_payment_component_tokenize" value="0" />
        </span>
    </p>
<?php } ?>
