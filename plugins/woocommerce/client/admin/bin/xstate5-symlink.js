/**
 * Patches @xstate5/react to resolve xstate from the xstate5 alias.
 *
 * See https://github.com/woocommerce/woocommerce/pull/45548 for context.
 */
const path = require( 'path' );

require( 'fs-extra' ).ensureSymlinkSync(
	path.join( __dirname, '../node_modules/xstate5' ),
	path.join( __dirname, '../node_modules/@xstate5/react/node_modules/xstate' )
);
