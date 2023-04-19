const { execSync } = require( 'child_process' );

/**
 * Executes a WP-CLI command using wp-env.
 * 
 * @param {string}  input The CLI command input.
 * @param {boolean} exitOnError Indicates whether or not the process should exit if the CLI returns an error.
 */
function runWPCLI( input, exitOnError = false ) {
    try {
        execSync( 
            'wp-env run tests-cli "wp ' + input.replace(/"/g, '\\"') + '"',
            { 
                stdio: [ 'ignore', 'ignore', 'pipe' ],
                encoding: 'utf-8'
            }
        );
    } catch (e) {
        // Only display the WP-CLI error if possible.
        const errorMatch = e.stderr.match(/(Error: [^\n]+)\n?/);
        if ( ! errorMatch ) {
            console.error( e.stderr );
        } else {
            console.error( errorMatch[1] );
        }
        
        if ( exitOnError ) {
            process.exit( 1 );
        }
    }
}

console.log( 'Activating "twentynineteen" Theme...' );
runWPCLI( 'theme activate twentynineteen', true );

console.log( 'Activating Pretty Permalinks...' );
runWPCLI( 'rewrite structure \'/%postname%/\' --hard', true );

console.log( 'Creating Customer...' );
runWPCLI( 'user create customer customer@woocommercecoree2etestsuite.com \
--user_pass=password \
--role=subscriber \
--first_name=\'Jane\' \
--last_name=\'Smith\' \
--user_registered=\'2022-01-01 12:23:45\'' );

console.log( 'Setting Blog Name...' );
runWPCLI( 'option update blogname "WooCommerce Core E2E Test Suite"' );

if ( process.env.ENABLE_HPOS ) {
    console.log( 'Enabling HPOS Feature...' );
    runWPCLI( 'plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/b031465052fc3e04b17624acbeeb2569ef4d5301.zip --activate' );
}

if ( process.env.ENABLE_NEW_PRODUCT_EDITOR ) {
    console.log( 'Enabling Product Editor Feature...' );
    runWPCLI( 'plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/b031465052fc3e04b17624acbeeb2569ef4d5301.zip --activate' );
}

if ( process.env.ENABLE_TRACKING ) {
    console.log( 'Enabling Tracking...' );
    runWPCLI( 'option update woocommerce_allow_tracking "yes"' );
}
