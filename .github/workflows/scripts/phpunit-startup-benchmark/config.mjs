import { createHash } from 'node:crypto';
import { existsSync } from 'node:fs';
import path from 'node:path';

const hash = ( value ) =>
	createHash( 'sha256' ).update( value ).digest( 'hex' );

/** Transform generated files, keeping all runtime mounts and configuration. */
function prepare( {
	compose,
	dockerfiles,
	manifest,
	variant,
	cacheRoot,
	lockHash,
} ) {
	if (
		! [
			'baseline',
			'cleanup',
			'cache-cold',
			'cache-warm',
			'cache-miss',
			'cache-incompatible',
		].includes( variant )
	) {
		throw new Error( `Unknown variant: ${ variant }` );
	}
	compose = structuredClone( compose );
	const activeFiles = new Set(
		Object.values( compose.services ).map(
			( service ) => service.build?.dockerfile
		)
	);
	dockerfiles = Object.fromEntries(
		Object.entries( dockerfiles ).filter( ( [ name ] ) =>
			activeFiles.has( name )
		)
	);
	const pin = ( name ) => {
		const pinned = manifest.images[ name ];
		if ( ! pinned || ! /@sha256:[a-f0-9]{64}$/.test( pinned ) ) {
			throw new Error( `Unpinned image: ${ name }` );
		}
		return pinned;
	};
	for ( const [ name, contents ] of Object.entries( dockerfiles ) ) {
		dockerfiles[ name ] = contents.replace(
			/^FROM (\S+)$/gm,
			( match, image ) => `FROM ${ pin( image ) }`
		);
		if ( variant === 'cleanup' ) {
			const lines = dockerfiles[ name ].split( '\n' );
			const unused = lines.filter( ( line ) =>
				line.startsWith(
					'RUN composer global require --dev phpunit/phpunit:'
				)
			);
			if ( unused.length !== 1 ) {
				throw new Error(
					`Expected one global PHPUnit installation in ${ name }`
				);
			}
			dockerfiles[ name ] = lines
				.filter( ( line ) => line !== unused[ 0 ] )
				.join( '\n' );
		}
	}
	if ( variant === 'cleanup' ) {
		delete compose.services.phpmyadmin;
		delete compose.services[ 'tests-phpmyadmin' ];
	}
	const cacheDirectories = {};
	const cacheHits = [];
	for ( const [ serviceName, service ] of Object.entries(
		compose.services
	) ) {
		if ( service.image ) {
			service.image = pin( service.image );
		}
		if ( ! service.build || ! variant.startsWith( 'cache-' ) ) {
			continue;
		}
		const identity = hash(
			JSON.stringify( {
				dockerfile: dockerfiles[ service.build.dockerfile ],
				args: service.build.args,
				platform: 'linux/amd64',
				lockHash,
				// Deliberately invalidate restored cache without changing the runtime.
				salt: variant === 'cache-incompatible' ? 'incompatible' : '',
			} )
		);
		const directory = path.join( cacheRoot, serviceName, identity );
		cacheDirectories[ serviceName ] = directory;
		if ( variant === 'cache-cold' ) {
			service.build.cache_to = [
				`type=local,dest=${ directory },mode=max`,
			];
		} else if ( existsSync( path.join( directory, 'index.json' ) ) ) {
			service.build.cache_from = [ `type=local,src=${ directory }` ];
			cacheHits.push( serviceName );
		}
	}
	return { compose, dockerfiles, cacheDirectories, cacheHits };
}
export { prepare };
