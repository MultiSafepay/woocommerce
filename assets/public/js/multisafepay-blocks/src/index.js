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
