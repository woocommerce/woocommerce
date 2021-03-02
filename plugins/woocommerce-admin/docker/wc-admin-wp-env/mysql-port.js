/**
 * External dependencies
 */
const execSync = require( 'child_process' ).execSync;
const path = require( 'path' );
const cwd = require( 'path' ).dirname( require.main.filename );
const crypto = require( 'crypto' );

const configPath = path.resolve( `${ cwd }/../../.wp-env.json` );
const wpEnvHash = crypto
	.createHash( 'md5' )
	.update( configPath )
	.digest( 'hex' );

const containerId = execSync(
	`docker ps -q --filter="name=^${ wpEnvHash }_mysql"`
)
	.toString()
	.trim();

const mysqlPort = execSync( `docker port ${ containerId } 3306/tcp` )
	.toString()
	.split( ':' )[ 1 ]
	.trim();

// eslint-disable-next-line no-console
console.log( `Mysql port: ${ mysqlPort }` );
