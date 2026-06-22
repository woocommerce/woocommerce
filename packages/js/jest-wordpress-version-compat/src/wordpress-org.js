'use strict';

const { spawnSync } = require( 'node:child_process' );

const CORE_VERSION_CHECK_URL =
	'https://api.wordpress.org/core/version-check/1.7/';
const GUTENBERG_PLUGIN_INFO_URL =
	'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request%5Bslug%5D=gutenberg';

function fetchJson( url, description ) {
	const result = spawnSync(
		process.execPath,
		[
			'-e',
			`
const url = process.argv[ 1 ];
fetch( url )
	.then( ( response ) => {
		if ( ! response.ok ) {
			throw new Error( \`HTTP \${ response.status } \${ response.statusText }\` );
		}

		return response.text();
	} )
	.then( ( body ) => {
		process.stdout.write( body );
	} )
	.catch( ( error ) => {
		console.error( error.message );
		process.exit( 1 );
	} );
`,
			url,
		],
		{
			encoding: 'utf8',
			stdio: 'pipe',
		}
	);

	if ( result.status !== 0 ) {
		throw new Error(
			[
				`Failed to resolve ${ description } from WordPress.org.`,
				result.stdout,
				result.stderr,
			]
				.filter( Boolean )
				.join( '\n' )
		);
	}

	const trimmedOutput = result.stdout.trim();

	if ( ! trimmedOutput ) {
		throw new Error(
			`WordPress.org returned an empty response for ${ description }.`
		);
	}

	return JSON.parse( trimmedOutput );
}

function getMajorMinorVersion( version ) {
	const match = String( version ).match( /^(\d+\.\d+)/ );

	return match ? match[ 1 ] : undefined;
}

function compareVersions( first, second ) {
	const firstParts = first.split( '.' ).map( Number );
	const secondParts = second.split( '.' ).map( Number );
	const length = Math.max( firstParts.length, secondParts.length );

	for ( let index = 0; index < length; index++ ) {
		const firstPart = firstParts[ index ] || 0;
		const secondPart = secondParts[ index ] || 0;

		if ( firstPart !== secondPart ) {
			return firstPart - secondPart;
		}
	}

	return 0;
}

function resolveWordPressCoreVersion( offset = 0 ) {
	const response = fetchJson(
		CORE_VERSION_CHECK_URL,
		'WordPress core version metadata'
	);
	const versions = new Set(
		( response.offers || [] )
			.map( ( offer ) => getMajorMinorVersion( offer.current ) )
			.filter( Boolean )
	);
	const sortedVersions = [ ...versions ].sort( compareVersions );
	const version = sortedVersions.at( -1 - offset );

	if ( ! version ) {
		throw new Error(
			'WordPress.org did not return enough WordPress core versions.'
		);
	}

	return version;
}

function resolveGutenbergPluginVersion() {
	const response = fetchJson(
		GUTENBERG_PLUGIN_INFO_URL,
		'Gutenberg plugin version metadata'
	);

	if ( ! response.version ) {
		throw new Error(
			'WordPress.org did not return a Gutenberg plugin version.'
		);
	}

	return String( response.version );
}

function resolveWordPressVersionTarget( wpVersion ) {
	if ( wpVersion === 'gutenberg' ) {
		const version = resolveGutenbergPluginVersion();

		return {
			distTag: 'latest',
			source: 'wordpress.org/plugins/info',
			version,
		};
	}

	const offset = wpVersion === 'latest-1' ? 1 : 0;
	const version = resolveWordPressCoreVersion( offset );

	return {
		distTag: `wp-${ version }`,
		source: 'wordpress.org/core/version-check',
		version,
	};
}

module.exports = {
	resolveWordPressVersionTarget,
};
