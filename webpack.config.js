const path                              = require( 'path' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const defaultConfig                     = require( '@wordpress/scripts/config/webpack.config' );

function requestToExternal( request ) {
    if ( request === '@woocommerce/blocks-registry' ) {
        return [ 'wc', 'wcBlocksRegistry' ];
    }
}

function requestToHandle( request ) {
    if ( request === '@woocommerce/blocks-registry' ) {
        return 'wc-blocks-registry';
    }
}

module.exports = {
    ...defaultConfig,
    entry: {
        'index': path.resolve( __dirname, 'assets/public/js/multisafepay-blocks/src/index.js' ),
    },
    output: {
        path: path.resolve( __dirname, 'assets/public/js/multisafepay-blocks/build' ),
        filename: '[name].js',
    },
    plugins: [
        ...defaultConfig.plugins.filter(
            ( plugin ) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
        ),
        new DependencyExtractionWebpackPlugin( { requestToExternal, requestToHandle } ),
    ],
};
