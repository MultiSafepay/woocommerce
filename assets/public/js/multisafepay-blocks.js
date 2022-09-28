const registerMultiSafepayPaymentMethods = ({wc, multisafepay_gateways}) => {
    const { registerPaymentMethod }      = wc.wcBlocksRegistry;

    multisafepay_gateways.forEach(
        ( gateway ) => { registerPaymentMethod( createOptions( gateway ) ); }
    );
}

const createOptions = ( gateway ) => {
    return {
        name: gateway.title,
        label: gateway.title,
        paymentMethodId: gateway.paymentMethodId,
        edit: React.createElement( 'div', null, '' ),
        canMakePayment: () => {
            return true
        },
        ariaLabel: gateway.title,
        content: React.createElement( 'div', null, '' )
    }
}

registerMultiSafepayPaymentMethods( window )
