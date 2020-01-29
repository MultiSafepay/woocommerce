jQuery('body').on(
    'updated_checkout',
    function () {
        document.getElementsByClassName("wc_payment_method payment_method_multisafepay_applepay")[0].style.display = "none";
        try {
            if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
                document.getElementsByClassName("wc_payment_method payment_method_multisafepay_applepay")[0].style.display = "block";
            }
        } catch (error) {
            console.warn('Apple Pay is not supported:', error);
        }
    }
);
