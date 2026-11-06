import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { createHash } from 'node:crypto';
import { prepare } from './config.mjs';

if ( process.env.CI !== 'true' ) {
	throw new Error( 'Run this benchmark only on an isolated CI runner.' );
}
const root = process.cwd();
const plugin = path.join( root, 'plugins/woocommerce' );
const pluginRequire = createRequire( path.join( plugin, 'package.json' ) );
const envPackage = pluginRequire.resolve( '@wordpress/env/package.json' );
const envRoot = path.dirname( envPackage );
const envRequire = createRequire( envPackage );
const yaml = envRequire( 'js-yaml' );
const dockerConfig = envRequire(
	path.join( envRoot, 'lib/runtime/docker/docker-config.js' )
);
const manifest = JSON.parse(
	fs.readFileSync( process.env.BENCH_INPUTS, 'utf8' )
);
const variant = process.env.BENCH_VARIANT;
const resultDirectory = process.env.BENCH_RESULTS;
fs.mkdirSync( resultDirectory, { recursive: true } );
const lockHash = createHash( 'sha256' )
	.update( fs.readFileSync( path.join( root, 'pnpm-lock.yaml' ) ) )
	.digest( 'hex' );

fs.writeFileSync(
	path.join( plugin, '.wp-env.test.override.json' ),
	JSON.stringify( {
		core: manifest.wordpress,
		phpVersion: '8.5',
		phpmyadmin: false,
		themes: manifest.themes,
	} )
);

const originalWrite = dockerConfig.writeDockerFiles;
dockerConfig.writeDockerFiles = async ( config ) => {
	await originalWrite( config );
	const compose = yaml.load(
		fs.readFileSync( config.dockerComposeConfigPath, 'utf8' )
	);
	const names = fs
		.readdirSync( config.workDirectoryPath )
		.filter( ( name ) => name.endsWith( '.Dockerfile' ) );
	const dockerfiles = Object.fromEntries(
		names.map( ( name ) => [
			name,
			fs.readFileSync(
				path.join( config.workDirectoryPath, name ),
				'utf8'
			),
		] )
	);
	const prepared = prepare( {
		compose,
		dockerfiles,
		manifest,
		variant,
		lockHash,
		cacheRoot: process.env.BENCH_CACHE,
	} );
	if ( variant === 'cache-warm' && prepared.cacheHits.length !== 2 ) {
		throw new Error(
			'Warm sample did not find both compatible image caches.'
		);
	}
	if (
		[ 'cache-miss', 'cache-incompatible' ].includes( variant ) &&
		prepared.cacheHits.length
	) {
		throw new Error( 'Fallback sample unexpectedly accepted a cache.' );
	}
	fs.writeFileSync(
		config.dockerComposeConfigPath,
		yaml.dump( prepared.compose )
	);
	for ( const [ name, contents ] of Object.entries( prepared.dockerfiles ) ) {
		fs.writeFileSync(
			path.join( config.workDirectoryPath, name ),
			contents
		);
		fs.writeFileSync( path.join( resultDirectory, name ), contents );
	}
	fs.copyFileSync(
		config.dockerComposeConfigPath,
		path.join( resultDirectory, 'compose.yml' )
	);
	fs.writeFileSync(
		path.join( resultDirectory, 'environment.json' ),
		JSON.stringify(
			{
				variant,
				lockHash,
				manifest,
				cacheDirectories: prepared.cacheDirectories,
				cacheHits: prepared.cacheHits,
				workDirectoryPath: config.workDirectoryPath,
				dockerComposeConfigPath: config.dockerComposeConfigPath,
			},
			null,
			2
		)
	);
	return config;
};

process.chdir( plugin );
process.env.PATH = [
	path.join( plugin, 'node_modules/.bin' ),
	process.env.PATH,
].join( path.delimiter );
const bin = path.join( envRoot, 'bin/wp-env' );
process.argv = [
	process.execPath,
	bin,
	'--config',
	'.wp-env.test.json',
	'start',
	'--update',
	'--debug',
];
envRequire( bin );
