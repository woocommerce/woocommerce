'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const { prepare } = require( './cache' );

const nodeModulesTransformPattern =
	/^node_modules\/((?:@[^/]+\/)?[^/]+)\/(.+)$/;
const internalJsTestsDirectory = path.resolve(
	__dirname,
	'../../internal-js-tests'
);
const singletonPackages = [ 'react', 'react-dom' ];

function escapeRegExp( value ) {
	return value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}

function getPackagePath( packageName ) {
	return packageName.split( '/' ).join( '/' );
}

// WordPress packages can depend on React singletons. When a compatibility cache
// installs React, keep React and React DOM imports pinned to that same cache so
// JSX runtimes and hooks do not resolve to a second copy. Skip the mapping when
// the cache does not contain React, otherwise non-React packages would point
// subpath imports like react/jsx-runtime at files that were never installed.
function getCachedPackagePath( packageName, cacheDirectory ) {
	return path.join(
		cacheDirectory,
		'node_modules',
		...packageName.split( '/' )
	);
}

function getCachedPackageJsonPath( cacheDirectory, packageName ) {
	return path.join(
		getCachedPackagePath( packageName, cacheDirectory ),
		'package.json'
	);
}

function isSingletonModuleNameMapperPattern( pattern ) {
	return singletonPackages.some(
		( packageName ) =>
			pattern === `^${ packageName }$` ||
			pattern.startsWith( `^${ packageName }/` )
	);
}

function removeSingletonModuleNameMapperEntries( moduleNameMapper = {} ) {
	return Object.fromEntries(
		Object.entries( moduleNameMapper ).filter(
			( [ pattern ] ) => ! isSingletonModuleNameMapperPattern( pattern )
		)
	);
}

function createSingletonModuleNameMapper( { cacheDirectory } ) {
	return singletonPackages.reduce( ( moduleNameMapper, packageName ) => {
		const packagePath = getCachedPackagePath( packageName, cacheDirectory );

		if ( ! fs.existsSync( path.join( packagePath, 'package.json' ) ) ) {
			return moduleNameMapper;
		}

		moduleNameMapper[ `^${ packageName }$` ] = packagePath;
		moduleNameMapper[
			`^${ packageName }/(.*?)(?:\\.js)?$`
		] = `${ packagePath }/$1.js`;

		return moduleNameMapper;
	}, {} );
}

function createJestModuleNameMapper( options = {} ) {
	return {
		...Object.entries( options.packagePaths ).reduce(
			( moduleNameMapper, [ packageName, packagePath ] ) => {
				moduleNameMapper[ `^${ packageName }$` ] = packagePath;

				return moduleNameMapper;
			},
			{}
		),
		...createSingletonModuleNameMapper( options ),
	};
}

function getCachedTransformPattern( {
	cacheDirectory,
	packageName,
	filePattern,
} ) {
	return [
		'node_modules/.cache/jest-wordpress-version-compat',
		escapeRegExp( path.basename( cacheDirectory ) ),
		'node_modules',
		getPackagePath( packageName ),
		filePattern,
	].join( '/' );
}

function normalizeTransformer( transformer, { cwd = process.cwd() } = {} ) {
	if ( transformer === 'babel-jest' ) {
		return require.resolve( 'babel-jest', {
			paths: [ internalJsTestsDirectory, cwd, __dirname ],
		} );
	}

	if ( Array.isArray( transformer ) && transformer[ 0 ] === 'babel-jest' ) {
		return [
			require.resolve( 'babel-jest', {
				paths: [ internalJsTestsDirectory, cwd, __dirname ],
			} ),
			...transformer.slice( 1 ),
		];
	}

	return transformer;
}

function getCachedTransformEntries( { cacheDirectory, transform = {}, cwd } ) {
	return Object.entries( transform ).reduce(
		( entries, [ pattern, transformer ] ) => {
			const match = pattern.match( nodeModulesTransformPattern );

			if ( ! match ) {
				return entries;
			}

			const [ , packageName, filePattern ] = match;

			if (
				! fs.existsSync(
					getCachedPackageJsonPath( cacheDirectory, packageName )
				)
			) {
				return entries;
			}

			entries[
				getCachedTransformPattern( {
					cacheDirectory,
					packageName,
					filePattern,
				} )
			] = normalizeTransformer( transformer, { cwd } );

			return entries;
		},
		{}
	);
}

function getCachedTransformIgnorePackageNames( { cacheDirectory, transform } ) {
	return Object.keys( transform )
		.map( ( pattern ) => pattern.match( nodeModulesTransformPattern ) )
		.filter( Boolean )
		.map( ( [ , packageName ] ) => packageName )
		.filter( ( packageName ) =>
			fs.existsSync(
				getCachedPackageJsonPath( cacheDirectory, packageName )
			)
		);
}

function getCachedTransformIgnorePattern( cacheDirectory, packageNames ) {
	const packageNamesPattern = packageNames
		.map( getPackagePath )
		.map( escapeRegExp )
		.join( '|' );

	return [
		'\\.cache/jest-wordpress-version-compat',
		escapeRegExp( path.basename( cacheDirectory ) ),
		'node_modules',
		`(?:${ packageNamesPattern })`,
	].join( '/' );
}

function getTransformIgnorePatterns( {
	cacheDirectory,
	transform,
	transformIgnorePatterns,
} ) {
	const packageNames = getCachedTransformIgnorePackageNames( {
		cacheDirectory,
		transform,
	} );

	if ( packageNames.length === 0 ) {
		return transformIgnorePatterns;
	}

	const cachedTransformIgnorePattern = getCachedTransformIgnorePattern(
		cacheDirectory,
		packageNames
	);
	const nodeModulesNegativeLookahead = 'node_modules/(?!(?:';

	return transformIgnorePatterns.map( ( pattern ) => {
		if ( ! pattern.includes( nodeModulesNegativeLookahead ) ) {
			return pattern;
		}

		return pattern.replace(
			nodeModulesNegativeLookahead,
			`${ nodeModulesNegativeLookahead }${ cachedTransformIgnorePattern }|`
		);
	} );
}

function createCachedTransformConfig( {
	cacheDirectory,
	transform = {},
	transformIgnorePatterns = [],
	cwd,
} ) {
	const cachedTransformEntries = getCachedTransformEntries( {
		cacheDirectory,
		transform,
		cwd,
	} );

	if ( Object.keys( cachedTransformEntries ).length === 0 ) {
		return {};
	}

	return {
		transform: {
			...cachedTransformEntries,
			...transform,
		},
		transformIgnorePatterns: getTransformIgnorePatterns( {
			cacheDirectory,
			transform,
			transformIgnorePatterns,
		} ),
	};
}

function withWordPressDependencyCompat( jestConfig = {}, options = {} ) {
	const preparedCache = prepare( options );
	const cachedTransformConfig = createCachedTransformConfig( {
		cacheDirectory: preparedCache.cacheDirectory,
		transform: jestConfig.transform,
		transformIgnorePatterns: jestConfig.transformIgnorePatterns,
		cwd: options.cwd,
	} );

	return {
		...jestConfig,
		moduleNameMapper: {
			...removeSingletonModuleNameMapperEntries(
				jestConfig.moduleNameMapper
			),
			...createJestModuleNameMapper( preparedCache ),
		},
		...cachedTransformConfig,
	};
}

module.exports = {
	withWordPressDependencyCompat,
};
