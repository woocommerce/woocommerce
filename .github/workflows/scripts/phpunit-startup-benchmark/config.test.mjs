import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mkdtempSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { prepare } from './config.mjs';

const digest = `sha256:${ 'a'.repeat( 64 ) }`;
const fixture = () => ( {
	compose: {
		services: {
			mysql: {
				image: 'mariadb:lts',
				volumes: [ 'mysql:/var/lib/mysql' ],
			},
			phpmyadmin: { image: 'phpmyadmin' },
			wordpress: {
				build: {
					dockerfile: 'WordPress.Dockerfile',
					args: { HOST_UID: 1001 },
				},
				volumes: [
					'/checkout:/var/www/html/wp-content/plugins/woocommerce',
				],
			},
			cli: {
				build: {
					dockerfile: 'CLI.Dockerfile',
					args: { HOST_UID: 1001 },
				},
			},
		},
		volumes: { mysql: {} },
	},
	dockerfiles: {
		'WordPress.Dockerfile':
			'FROM wordpress:php8.5\nRUN apt-get -qy install git\nRUN composer global require --dev phpunit/phpunit:"^10.0"\nUSER root\n',
		'CLI.Dockerfile':
			'FROM wordpress:cli-php8.5\nRUN apk add sudo\nRUN composer global require --dev phpunit/phpunit:"^10.0"\nUSER root\n',
	},
	manifest: {
		images: Object.fromEntries(
			[
				'mariadb:lts',
				'phpmyadmin',
				'wordpress:php8.5',
				'wordpress:cli-php8.5',
			].map( ( name ) => [ name, `${ name }@${ digest }` ] )
		),
	},
	lockHash: 'lock-a',
	variant: 'baseline',
} );

test( 'cleanup removes unused tooling while preserving database and checkout mounts', () => {
	const input = fixture();
	const result = prepare( { ...input, variant: 'cleanup' } );
	assert.equal( result.compose.services.phpmyadmin, undefined );
	assert.deepEqual(
		result.compose.services.mysql.volumes,
		input.compose.services.mysql.volumes
	);
	assert.deepEqual(
		result.compose.services.wordpress.volumes,
		input.compose.services.wordpress.volumes
	);
	assert.match( result.dockerfiles[ 'CLI.Dockerfile' ], /RUN apk add sudo/ );
	assert.doesNotMatch(
		result.dockerfiles[ 'CLI.Dockerfile' ],
		/composer global require/
	);
	assert.match( result.dockerfiles[ 'WordPress.Dockerfile' ], /USER root/ );
} );

test( 'baseline pins every image without enabling cache or removing tooling', () => {
	const result = prepare( fixture() );
	assert.equal(
		result.compose.services.mysql.image,
		`mariadb:lts@${ digest }`
	);
	assert.equal(
		result.compose.services.phpmyadmin.image,
		`phpmyadmin@${ digest }`
	);
	assert.match(
		result.dockerfiles[ 'CLI.Dockerfile' ],
		new RegExp( `^FROM wordpress:cli-php8.5@${ digest }\\n` )
	);
	assert.match(
		result.dockerfiles[ 'CLI.Dockerfile' ],
		/composer global require/
	);
	assert.equal( result.compose.services.cli.build.cache_from, undefined );
} );

test( 'cache reuse accepts matching inputs and rejects changed Dockerfiles, users, and lockfiles', ( t ) => {
	const cacheRoot = mkdtempSync( path.join( tmpdir(), 'wc-benchmark-' ) );
	t.after( () => rmSync( cacheRoot, { recursive: true, force: true } ) );
	const input = { ...fixture(), variant: 'cache-cold', cacheRoot };
	const cold = prepare( input );
	for ( const directory of Object.values( cold.cacheDirectories ) ) {
		mkdirSync( directory, { recursive: true } );
		writeFileSync( path.join( directory, 'index.json' ), '{}' );
	}
	const warm = prepare( { ...input, variant: 'cache-warm' } );
	assert.equal( warm.cacheHits.length, 2 );
	assert.equal( warm.compose.services.cli.build.cache_to, undefined );
	assert.notEqual(
		cold.cacheDirectories.cli,
		cold.cacheDirectories.wordpress
	);
	for ( const change of [
		{ lockHash: 'lock-b' },
		{
			dockerfiles: {
				...input.dockerfiles,
				'CLI.Dockerfile': `${ input.dockerfiles[ 'CLI.Dockerfile' ] }RUN echo changed\n`,
			},
		},
		{
			compose: {
				...input.compose,
				services: {
					...input.compose.services,
					cli: {
						build: {
							...input.compose.services.cli.build,
							args: { HOST_UID: 2000 },
						},
					},
				},
			},
		},
	] ) {
		const changed = prepare( {
			...input,
			...change,
			variant: 'cache-warm',
		} );
		assert.equal(
			changed.compose.services.cli.build.cache_from,
			undefined
		);
	}
} );

test( 'missing cache retains a buildable fresh environment and unpinned images fail closed', () => {
	const result = prepare( {
		...fixture(),
		variant: 'cache-miss',
		cacheRoot: '/nonexistent-wc-benchmark-cache',
	} );
	assert.deepEqual( result.cacheHits, [] );
	assert.equal(
		result.compose.services.cli.build.dockerfile,
		'CLI.Dockerfile'
	);
	assert.throws(
		() => prepare( { ...fixture(), manifest: { images: {} } } ),
		/Unpinned image/
	);
} );
