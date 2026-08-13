'use strict';

const { spawnSync } = require( 'node:child_process' );

const { getNpmDistTagForWordPressVersion } = require( './metadata' );

function parseNpmViewVersion( packageSpec, stdout ) {
	const trimmedOutput = stdout.trim();

	if ( ! trimmedOutput ) {
		throw new Error(
			`npm returned an empty version for ${ packageSpec }.`
		);
	}

	try {
		const parsedOutput = JSON.parse( trimmedOutput );

		if ( Array.isArray( parsedOutput ) ) {
			return parsedOutput[ parsedOutput.length - 1 ];
		}

		return parsedOutput;
	} catch ( error ) {
		return trimmedOutput;
	}
}

function parseNpmViewDistTags( packageName, stdout ) {
	const trimmedOutput = stdout.trim();

	if ( ! trimmedOutput ) {
		throw new Error( `npm returned empty dist-tags for ${ packageName }.` );
	}

	const parsedOutput = JSON.parse( trimmedOutput );

	if ( ! parsedOutput || typeof parsedOutput !== 'object' ) {
		throw new Error(
			`npm returned invalid dist-tags for ${ packageName }.`
		);
	}

	return parsedOutput;
}

function compareWordPressDistTags( first, second ) {
	const firstVersion = first.replace( /^wp-/, '' ).split( '.' ).map( Number );
	const secondVersion = second
		.replace( /^wp-/, '' )
		.split( '.' )
		.map( Number );
	const length = Math.max( firstVersion.length, secondVersion.length );

	for ( let index = 0; index < length; index++ ) {
		const firstPart = firstVersion[ index ] || 0;
		const secondPart = secondVersion[ index ] || 0;

		if ( firstPart !== secondPart ) {
			return firstPart - secondPart;
		}
	}

	return 0;
}

function resolveWordPressDistTagFromNpm( packageName, offset = 0 ) {
	const result = spawnSync(
		'npm',
		[ 'view', packageName, 'dist-tags', '--json' ],
		{
			encoding: 'utf8',
			stdio: 'pipe',
		}
	);

	if ( result.status !== 0 ) {
		throw new Error(
			[
				`Failed to resolve ${ packageName } dist-tags from npm.`,
				result.stdout,
				result.stderr,
			]
				.filter( Boolean )
				.join( '\n' )
		);
	}

	const distTags = parseNpmViewDistTags( packageName, result.stdout );
	const wordpressDistTags = Object.keys( distTags ).filter( ( distTag ) =>
		/^wp-\d+\.\d+$/.test( distTag )
	);

	if ( wordpressDistTags.length === 0 ) {
		throw new Error(
			`npm did not return WordPress dist-tags for ${ packageName }.`
		);
	}

	const sortedWordPressDistTags = wordpressDistTags.sort(
		compareWordPressDistTags
	);
	const distTag = sortedWordPressDistTags.at( -1 - offset );

	if ( ! distTag ) {
		throw new Error(
			`npm did not return enough WordPress dist-tags for ${ packageName }.`
		);
	}

	return distTag;
}

function resolveWordPressPackageSpec( packageName, wpVersion, distTag ) {
	return `${ packageName }@${
		distTag || getNpmDistTagForWordPressVersion( wpVersion )
	}`;
}

function assertPackageDistTagExists( packageName, distTag ) {
	const result = spawnSync(
		'npm',
		[ 'view', packageName, 'dist-tags', '--json' ],
		{
			encoding: 'utf8',
			stdio: 'pipe',
		}
	);

	if ( result.status !== 0 ) {
		throw new Error(
			[
				`Failed to resolve ${ packageName } dist-tags from npm.`,
				result.stdout,
				result.stderr,
			]
				.filter( Boolean )
				.join( '\n' )
		);
	}

	const distTags = parseNpmViewDistTags( packageName, result.stdout );

	if ( ! Object.prototype.hasOwnProperty.call( distTags, distTag ) ) {
		throw new Error(
			`npm did not return dist-tag ${ distTag } for ${ packageName }.`
		);
	}
}

function resolvePackageVersionFromNpm( packageName, wpVersion, distTag ) {
	const resolvedDistTag =
		distTag || getNpmDistTagForWordPressVersion( wpVersion );
	assertPackageDistTagExists( packageName, resolvedDistTag );

	const packageSpec = resolveWordPressPackageSpec(
		packageName,
		wpVersion,
		resolvedDistTag
	);
	const result = spawnSync(
		'npm',
		[ 'view', packageSpec, 'version', '--json' ],
		{
			encoding: 'utf8',
			stdio: 'pipe',
		}
	);

	if ( result.status !== 0 ) {
		throw new Error(
			[
				`Failed to resolve ${ packageSpec } from npm.`,
				result.stdout,
				result.stderr,
			]
				.filter( Boolean )
				.join( '\n' )
		);
	}

	return parseNpmViewVersion( packageSpec, result.stdout );
}

function resolvePackageDependenciesFromNpm( packageName, version ) {
	const packageSpec = `${ packageName }@${ version }`;
	const result = spawnSync(
		'npm',
		[ 'view', packageSpec, 'dependencies', '--json' ],
		{
			encoding: 'utf8',
			stdio: 'pipe',
		}
	);

	if ( result.status !== 0 ) {
		throw new Error(
			[
				`Failed to resolve ${ packageSpec } dependencies from npm.`,
				result.stdout,
				result.stderr,
			]
				.filter( Boolean )
				.join( '\n' )
		);
	}

	const trimmedOutput = result.stdout.trim();

	if ( ! trimmedOutput ) {
		return {};
	}

	const dependencies = JSON.parse( trimmedOutput );

	if ( ! dependencies || typeof dependencies !== 'object' ) {
		return {};
	}

	return dependencies;
}

function installPackages( cacheDirectory ) {
	const result = spawnSync(
		'npm',
		[
			'install',
			'--prefix',
			cacheDirectory,
			'--package-lock=false',
			'--ignore-scripts',
			'--no-audit',
			'--no-fund',
			'--save-exact',
		],
		{
			encoding: 'utf8',
			stdio: 'pipe',
		}
	);

	if ( result.status !== 0 ) {
		throw new Error(
			[
				'Failed to install cached @wordpress packages.',
				result.stdout,
				result.stderr,
			]
				.filter( Boolean )
				.join( '\n' )
		);
	}
}

module.exports = {
	installPackages,
	resolvePackageDependenciesFromNpm,
	resolvePackageVersionFromNpm,
	resolveWordPressDistTagFromNpm,
};
