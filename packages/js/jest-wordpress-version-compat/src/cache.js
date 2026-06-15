'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { spawnSync } = require( 'node:child_process' );

const {
	getNpmDistTagForWordPressVersion,
	isBundledPackage,
	isWordPressPackage,
} = require( './metadata' );

const CONFIG_KEY = 'wpDependencyCompat';
const CACHE_DIRECTORY_NAME = 'jest-wordpress-version-compat';
const latestWordPressDistTagCache = new Map();

function findUp( fileName, startDirectory = process.cwd() ) {
	let currentDirectory = path.resolve( startDirectory );

	while ( true ) {
		const candidate = path.join( currentDirectory, fileName );

		if ( fs.existsSync( candidate ) ) {
			return candidate;
		}

		const parentDirectory = path.dirname( currentDirectory );

		if ( parentDirectory === currentDirectory ) {
			return undefined;
		}

		currentDirectory = parentDirectory;
	}
}

function findWorkspaceRoot( cwd = process.cwd() ) {
	const workspaceFile = findUp( 'pnpm-workspace.yaml', cwd );

	if ( workspaceFile ) {
		return path.dirname( workspaceFile );
	}

	const packageFile = findUp( 'package.json', cwd );

	return packageFile ? path.dirname( packageFile ) : path.resolve( cwd );
}

function getDefaultCacheRoot( cwd = process.cwd() ) {
	return path.join(
		findWorkspaceRoot( cwd ),
		'node_modules',
		'.cache',
		CACHE_DIRECTORY_NAME
	);
}

function getSelectedWordPressVersion() {
	return process.env.WP_VERSION || 'latest';
}

function getCacheDirectory( {
	wpVersion = getSelectedWordPressVersion(),
	cacheRoot,
	cwd = process.cwd(),
} = {} ) {
	return path.join( cacheRoot || getDefaultCacheRoot( cwd ), wpVersion );
}

function getCachedPackagePath( packageName, options = {} ) {
	return path.join(
		getCacheDirectory( options ),
		'node_modules',
		...packageName.split( '/' )
	);
}

function isPackageCached( packageName, options = {} ) {
	return fs.existsSync(
		path.join(
			getCachedPackagePath( packageName, options ),
			'package.json'
		)
	);
}

function readJsonFile( filePath ) {
	return JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
}

function getInstalledPackageJsonPath( packageName, cwd = process.cwd() ) {
	return require.resolve( `${ packageName }/package.json`, {
		paths: [ cwd ],
	} );
}

function getInstalledPackageVersion( packageName, cwd = process.cwd() ) {
	try {
		const packageJsonPath = getInstalledPackageJsonPath( packageName, cwd );

		return readJsonFile( packageJsonPath ).version;
	} catch ( error ) {
		return undefined;
	}
}

function getInstalledPackagePath( packageName, cwd = process.cwd() ) {
	return path.dirname( getInstalledPackageJsonPath( packageName, cwd ) );
}

function findProjectPackageJson( cwd = process.cwd() ) {
	const packageFile = findUp( 'package.json', cwd );

	return packageFile ? readJsonFile( packageFile ) : {};
}

function getPackageJsonWordPressDependencies( packageJson ) {
	const dependencySections = [
		'dependencies',
		'devDependencies',
		'peerDependencies',
		'optionalDependencies',
	];
	const packages = new Set();

	for ( const section of dependencySections ) {
		for ( const [ packageName, versionSpec ] of Object.entries(
			packageJson[ section ] || {}
		) ) {
			const normalizedVersionSpec = String( versionSpec );

			if (
				isWordPressPackage( packageName ) &&
				! isBundledPackage( packageName ) &&
				normalizedVersionSpec.startsWith( 'catalog:wp-' ) &&
				normalizedVersionSpec !== 'catalog:wp-bundled'
			) {
				packages.add( packageName );
			}
		}
	}

	return [ ...packages ].sort();
}

function normalizePackageList( packages ) {
	if ( ! packages ) {
		return undefined;
	}

	const packageList = Array.isArray( packages ) ? packages : [ packages ];

	return packageList
		.flatMap( ( packageName ) => String( packageName ).split( ',' ) )
		.map( ( packageName ) => packageName.trim() )
		.filter( Boolean )
		.filter( isWordPressPackage )
		.filter( ( packageName ) => ! isBundledPackage( packageName ) )
		.sort();
}

function getConfiguredPackages( packageJson ) {
	return normalizePackageList( packageJson[ CONFIG_KEY ]?.packages );
}

function resolveRequestedPackages( {
	wpVersion = getSelectedWordPressVersion(),
	packages,
	cwd = process.cwd(),
} = {} ) {
	getNpmDistTagForWordPressVersion( wpVersion );

	const packageJson = findProjectPackageJson( cwd );

	return (
		normalizePackageList( packages ) ||
		getConfiguredPackages( packageJson ) ||
		getPackageJsonWordPressDependencies( packageJson )
	);
}

function ensureCachePackageJson( cacheDirectory, wpVersion ) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );

	const packageJsonPath = path.join( cacheDirectory, 'package.json' );

	if ( fs.existsSync( packageJsonPath ) ) {
		return;
	}

	fs.writeFileSync(
		packageJsonPath,
		JSON.stringify(
			{
				private: true,
				name: `jest-wordpress-version-compat-cache-${ wpVersion }`,
				description:
					'Generated cache for @wordpress package compatibility tests.',
			},
			null,
			2
		) + '\n'
	);
}

function getResolvedVersionsPath( cacheDirectory ) {
	return path.join( cacheDirectory, 'resolved-versions.json' );
}

function readResolvedVersions( cacheDirectory ) {
	const resolvedVersionsPath = getResolvedVersionsPath( cacheDirectory );

	if ( ! fs.existsSync( resolvedVersionsPath ) ) {
		return {};
	}

	return readJsonFile( resolvedVersionsPath );
}

function writeResolvedVersions( cacheDirectory, resolvedVersions ) {
	fs.mkdirSync( cacheDirectory, { recursive: true } );

	fs.writeFileSync(
		getResolvedVersionsPath( cacheDirectory ),
		JSON.stringify( resolvedVersions, null, 2 ) + '\n'
	);
}

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

function resolveNpmDistTag(
	packageName,
	wpVersion,
	cacheDirectory,
	{ preferCache = true } = {}
) {
	const distTag = getNpmDistTagForWordPressVersion( wpVersion );

	if ( distTag === 'latest' ) {
		return distTag;
	}

	const offset = distTag === 'wp-latest-1' ? 1 : 0;

	const cacheKey = [
		cacheDirectory || process.cwd(),
		packageName,
		distTag,
	].join( ':' );

	if ( preferCache && latestWordPressDistTagCache.has( cacheKey ) ) {
		return latestWordPressDistTagCache.get( cacheKey );
	}

	if ( cacheDirectory ) {
		const resolvedVersions = readResolvedVersions( cacheDirectory );
		const cachedDistTag = resolvedVersions.__distTags?.[ packageName ];

		if ( preferCache && cachedDistTag ) {
			latestWordPressDistTagCache.set( cacheKey, cachedDistTag );
			return cachedDistTag;
		}

		const latestDistTag = resolveWordPressDistTagFromNpm(
			packageName,
			offset
		);
		latestWordPressDistTagCache.set( cacheKey, latestDistTag );

		writeResolvedVersions( cacheDirectory, {
			...resolvedVersions,
			__distTags: {
				...( resolvedVersions.__distTags || {} ),
				[ packageName ]: latestDistTag,
			},
		} );

		return latestDistTag;
	}

	const latestDistTag = resolveWordPressDistTagFromNpm( packageName, offset );
	latestWordPressDistTagCache.set( cacheKey, latestDistTag );

	return latestDistTag;
}

function resolveWordPressPackageSpec(
	packageName,
	wpVersion,
	cacheDirectory,
	distTag
) {
	return `${ packageName }@${
		distTag || resolveNpmDistTag( packageName, wpVersion, cacheDirectory )
	}`;
}

function resolvePackageVersionFromNpm(
	packageName,
	wpVersion,
	cacheDirectory,
	distTag
) {
	const packageSpec = resolveWordPressPackageSpec(
		packageName,
		wpVersion,
		cacheDirectory,
		distTag
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

function resolvePackageVersion( packageName, wpVersion, cacheDirectory ) {
	if ( cacheDirectory ) {
		const cachedResolvedVersions = readResolvedVersions( cacheDirectory );
		const cachedDistTag =
			cachedResolvedVersions.__distTags?.[ packageName ];
		const distTag = resolveNpmDistTag(
			packageName,
			wpVersion,
			cacheDirectory,
			{
				preferCache: false,
			}
		);
		const resolvedVersions = readResolvedVersions( cacheDirectory );
		const cachedVersion = resolvedVersions[ packageName ];
		const isDynamicWordPressTarget =
			getNpmDistTagForWordPressVersion( wpVersion ).startsWith(
				'wp-latest'
			);

		if (
			cachedVersion &&
			( ! isDynamicWordPressTarget || cachedDistTag === distTag )
		) {
			return cachedVersion;
		}

		const packageVersion = resolvePackageVersionFromNpm(
			packageName,
			wpVersion,
			cacheDirectory,
			distTag
		);
		const latestResolvedVersions = readResolvedVersions( cacheDirectory );

		writeResolvedVersions( cacheDirectory, {
			...latestResolvedVersions,
			...( isDynamicWordPressTarget
				? {
						__distTags: {
							...( latestResolvedVersions.__distTags || {} ),
							[ packageName ]: distTag,
						},
				  }
				: {} ),
			[ packageName ]: packageVersion,
		} );

		return packageVersion;
	}

	return resolvePackageVersionFromNpm(
		packageName,
		wpVersion,
		cacheDirectory
	);
}

function toPackageSpec( packageName, wpVersion, { cacheDirectory } = {} ) {
	return `${ packageName }@${ resolvePackageVersion(
		packageName,
		wpVersion,
		cacheDirectory
	) }`;
}

function getPackagesRequiringCache( {
	packages,
	wpVersion,
	cacheDirectory,
	cwd = process.cwd(),
} ) {
	return packages.filter( ( packageName ) => {
		const installedVersion = getInstalledPackageVersion( packageName, cwd );
		const targetVersion = resolvePackageVersion(
			packageName,
			wpVersion,
			cacheDirectory
		);

		return installedVersion !== targetVersion;
	} );
}

function getPackagePaths( {
	packages,
	cachePackages,
	wpVersion,
	cacheRoot,
	cwd = process.cwd(),
} ) {
	const cachePackageSet = new Set( cachePackages );

	return packages.reduce( ( packagePaths, packageName ) => {
		packagePaths[ packageName ] = cachePackageSet.has( packageName )
			? getCachedPackagePath( packageName, {
					wpVersion,
					cacheRoot,
					cwd,
			  } )
			: getInstalledPackagePath( packageName, cwd );

		return packagePaths;
	}, {} );
}

function installPackages( packageSpecs, cacheDirectory ) {
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
			...packageSpecs,
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

function prepare( {
	wpVersion = getSelectedWordPressVersion(),
	packages,
	cwd = process.cwd(),
	cacheRoot,
	offline = process.env.WP_JEST_DEPENDENCY_COMPAT_OFFLINE === '1',
	logger = console,
} = {} ) {
	const selectedPackages = resolveRequestedPackages( {
		wpVersion,
		packages,
		cwd,
	} );
	const cacheDirectory = getCacheDirectory( { wpVersion, cacheRoot, cwd } );
	const cachePackages = getPackagesRequiringCache( {
		packages: selectedPackages,
		wpVersion,
		cacheDirectory,
		cwd,
	} );
	const missingPackages = cachePackages.filter(
		( packageName ) =>
			! isPackageCached( packageName, { wpVersion, cacheRoot, cwd } )
	);

	if ( missingPackages.length === 0 ) {
		return {
			cachePackages,
			cacheDirectory,
			installedPackages: [],
			packagePaths: getPackagePaths( {
				packages: selectedPackages,
				cachePackages,
				wpVersion,
				cacheRoot,
				cwd,
			} ),
			packages: selectedPackages,
			wpVersion,
		};
	}

	if ( offline ) {
		throw new Error(
			`Missing cached @wordpress packages for WordPress ${ wpVersion }: ${ missingPackages.join(
				', '
			) }. Unset WP_JEST_DEPENDENCY_COMPAT_OFFLINE or call prepareWordPressPackages() before running tests.`
		);
	}

	ensureCachePackageJson( cacheDirectory, wpVersion );

	const packageSpecs = missingPackages.map( ( packageName ) =>
		toPackageSpec( packageName, wpVersion, { cacheDirectory } )
	);

	logger.info?.(
		`Preparing ${ missingPackages.length } @wordpress package(s) for WordPress ${ wpVersion }.`
	);

	installPackages( packageSpecs, cacheDirectory );

	return {
		cachePackages,
		cacheDirectory,
		installedPackages: missingPackages,
		packagePaths: getPackagePaths( {
			packages: selectedPackages,
			cachePackages,
			wpVersion,
			cacheRoot,
			cwd,
		} ),
		packages: selectedPackages,
		wpVersion,
	};
}

function clearCache( { wpVersion, cacheRoot, cwd = process.cwd() } = {} ) {
	const directory = wpVersion
		? getCacheDirectory( { wpVersion, cacheRoot, cwd } )
		: cacheRoot || getDefaultCacheRoot( cwd );

	fs.rmSync( directory, { recursive: true, force: true } );

	return directory;
}

module.exports = {
	clearCache,
	findProjectPackageJson,
	findWorkspaceRoot,
	getCacheDirectory,
	getCachedPackagePath,
	getDefaultCacheRoot,
	getInstalledPackagePath,
	getInstalledPackageVersion,
	getPackageJsonWordPressDependencies,
	getPackagePaths,
	getPackagesRequiringCache,
	getSelectedWordPressVersion,
	isPackageCached,
	parseNpmViewDistTags,
	parseNpmViewVersion,
	prepare,
	resolveWordPressDistTagFromNpm,
	resolveNpmDistTag,
	resolvePackageVersionFromNpm,
	resolveRequestedPackages,
	toPackageSpec,
};
