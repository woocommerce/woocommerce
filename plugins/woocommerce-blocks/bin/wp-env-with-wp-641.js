const fs = require( 'fs' );
const path = require( 'path' );

const wpEnvRaw = fs.readFileSync(
	path.join( __dirname, '..', '.wp-env.json' )
);
const wpEnv = JSON.parse( wpEnvRaw );

// Pin the core version to 6.2.2 for Jest E2E test so we can keep the test
// passing when new WordPress versions are released. We do this because we're
// moving to Playwright and will abandon the Jest E2E tests once the migration
// is complete.
wpEnv.core = 'WordPress/WordPress#6.4.1';

// We write the new file to .wp-env.override.json (https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-override-json)
fs.writeFileSync(
	path.join( __dirname, '..', '.wp-env.override.json' ),
	JSON.stringify( wpEnv )
);
