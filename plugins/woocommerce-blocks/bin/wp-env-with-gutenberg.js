const fs = require( 'fs' );
const path = require( 'path' );

const wpEnvRaw = fs.readFileSync(
	path.join( __dirname, '..', '.wp-env.json' )
);
const wpEnv = JSON.parse( wpEnvRaw );
wpEnv.plugins.push(
	'https://downloads.wordpress.org/plugin/gutenberg.latest-stable.zip'
);
// We write the new file to .wp-env.override.json (https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-override-json)
fs.writeFileSync(
	path.join( __dirname, '..', '.wp-env.override.json' ),
	JSON.stringify( wpEnv )
);
