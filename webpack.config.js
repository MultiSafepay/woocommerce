const path          = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
    ...defaultConfig,
    entry: {
        'index': path.resolve( __dirname, 'assets/public/js/multisafepay-blocks/src/index.js' ),
    },
    output: {
        path: path.resolve( __dirname, 'assets/public/js/multisafepay-blocks/build' ),
        filename: '[name].js',
    },
};
