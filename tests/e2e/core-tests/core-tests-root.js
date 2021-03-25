/**
 * External dependencies
 */
 const path = require( 'path' );

 const getCoreTestsRoot = () => {
     // Figure out where we're installed.
     // Typically will be in node_modules/, but WooCommerce
     // uses a local file path (tests/e2e/env).
     let coreTestsPath = false;
     const moduleDir = path.dirname( require.resolve( '@woocommerce/e2e-environment' ) );
 
     if ( -1 < moduleDir.indexOf( 'node_modules' ) ) {
        coreTestsPath = moduleDir.split( 'node_modules' )[ 0 ];
     } else if ( -1 < moduleDir.indexOf( 'tests/e2e/env' ) ) {
        coreTestsPath = moduleDir.split( 'tests/e2e/env' )[ 0 ];
     }
 
     return coreTestsPath;
 };
 
 module.exports = getCoreTestsRoot;