import fs from 'node:fs/promises';
import path from 'node:path';
import Ajv from 'ajv';
import { lintPHP } from './lint-php.mjs';
import {
	getThemeDirectory,
	isMain,
	listThemeFiles,
	readThemeHeader,
} from './theme-utils.mjs';

async function fetchSchema( url ) {
	const response = await fetch( url, {
		signal: AbortSignal.timeout( 30000 ),
	} );
	if ( ! response.ok ) {
		throw new Error( `could not load ${ url }: ${ response.status }` );
	}
	return response.json();
}

function normalizeVersion( version ) {
	if ( ! /^\d+\.\d+(?:\.\d+)?$/.test( version ?? '' ) ) {
		return null;
	}

	return version.split( '.' ).map( Number );
}

function compareVersions( left, right ) {
	for ( let index = 0; index < 3; index++ ) {
		const difference = ( left[ index ] ?? 0 ) - ( right[ index ] ?? 0 );
		if ( difference !== 0 ) {
			return difference;
		}
	}

	return 0;
}

export async function validateTheme(
	themeDirectory,
	loadSchema = fetchSchema
) {
	const errors = [];
	const report = ( file, message ) =>
		errors.push( `${ file }: ${ message }` );
	const files = ( await listThemeFiles( themeDirectory ) ).map( ( file ) =>
		path.join( themeDirectory, file )
	);
	const themeSlug = path.basename( themeDirectory );
	for ( const requiredFile of [ 'theme.json', 'templates/index.html' ] ) {
		const file = path.join( themeDirectory, requiredFile );
		if ( ! files.includes( file ) ) {
			report( file, 'missing required block theme file' );
		}
	}
	const stylesheetPath = path.join( themeDirectory, 'style.css' );
	const stylesheet = await fs.readFile( stylesheetPath, 'utf8' );
	const requiredHeaders = [
		'Theme Name',
		'Author',
		'Description',
		'Requires at least',
		'Tested up to',
		'Requires PHP',
		'Version',
		'License',
		'License URI',
		'Text Domain',
	];

	for ( const header of requiredHeaders ) {
		if ( ! readThemeHeader( stylesheet, header ) ) {
			report( stylesheetPath, `missing required "${ header }" header` );
		}
	}

	const readme = await fs.readFile(
		path.join( themeDirectory, 'readme.txt' ),
		'utf8'
	);
	const version = readThemeHeader( stylesheet, 'Version' );
	if ( ! /^\d+\.\d+\.\d+$/.test( version ?? '' ) ) {
		report( stylesheetPath, '"Version" must use X.Y.Z format' );
	}
	const manifestPath = path.join( themeDirectory, 'package.json' );
	try {
		const manifest = JSON.parse(
			await fs.readFile( manifestPath, 'utf8' )
		);
		if ( manifest.version !== version ) {
			report( manifestPath, 'Version must match style.css' );
		}
	} catch ( error ) {
		report( manifestPath, error.message );
	}
	if ( readThemeHeader( readme, 'Stable tag' ) !== version ) {
		report( stylesheetPath, 'Version must match readme.txt Stable tag' );
	}
	if ( readThemeHeader( stylesheet, 'Text Domain' ) !== themeSlug ) {
		report(
			stylesheetPath,
			'Text Domain must match the theme directory name'
		);
	}

	const minimumWordPress = readThemeHeader( stylesheet, 'Requires at least' );
	const testedWordPress = readThemeHeader( stylesheet, 'Tested up to' );
	const minimumVersion = normalizeVersion( minimumWordPress );
	const testedVersion = normalizeVersion( testedWordPress );

	if ( ! minimumVersion ) {
		report(
			stylesheetPath,
			'"Requires at least" must be a valid WordPress version'
		);
	}
	if ( ! testedVersion ) {
		report(
			stylesheetPath,
			'"Tested up to" must be a valid WordPress version'
		);
	} else if (
		minimumVersion &&
		compareVersions( testedVersion, minimumVersion ) < 0
	) {
		report(
			stylesheetPath,
			'"Tested up to" cannot be lower than "Requires at least"'
		);
	}

	const jsonFiles = files.filter( ( file ) => file.endsWith( '.json' ) );
	const parsedJson = new Map();
	for ( const file of jsonFiles ) {
		try {
			parsedJson.set(
				file,
				JSON.parse( await fs.readFile( file, 'utf8' ) )
			);
		} catch ( error ) {
			report( file, `invalid JSON: ${ error.message }` );
		}
	}

	if ( minimumVersion ) {
		const schemaUrl = `https://schemas.wp.org/wp/${ minimumWordPress }/theme.json`;

		try {
			const schema = await loadSchema( schemaUrl );
			const ajv = new Ajv( {
				allErrors: true,
				loadSchema,
				strict: false,
			} );
			const validate = await ajv.compileAsync( schema );
			const themeJsonFiles = jsonFiles.filter(
				( file ) =>
					file === path.join( themeDirectory, 'theme.json' ) ||
					file.startsWith(
						path.join( themeDirectory, 'styles' ) + path.sep
					)
			);

			for ( const file of themeJsonFiles ) {
				const contents = parsedJson.get( file );
				if ( parsedJson.has( file ) && ! validate( contents ) ) {
					for ( const error of validate.errors ?? [] ) {
						report(
							file,
							`schema error at ${ error.instancePath || '/' }: ${ error.message }`
						);
					}
				}
			}
		} catch ( error ) {
			report( 'theme.json schema', error.message );
		}
	}

	const patternFiles = files.filter(
		( file ) =>
			file.startsWith(
				path.join( themeDirectory, 'patterns' ) + path.sep
			) && file.endsWith( '.php' )
	);
	const patternSlugs = new Map();

	for ( const file of patternFiles ) {
		const contents = await fs.readFile( file, 'utf8' );
		const title = contents
			.match( /^\s*\*\s+Title:\s*(.+)$/m )?.[ 1 ]
			.trim();
		const slug = contents.match( /^\s*\*\s+Slug:\s*(\S+)$/m )?.[ 1 ];

		if ( ! title ) {
			report( file, 'missing pattern Title header' );
		}
		if ( ! slug ) {
			report( file, 'missing pattern Slug header' );
			continue;
		}
		if ( patternSlugs.has( slug ) ) {
			report(
				file,
				`duplicates pattern slug declared by ${ patternSlugs.get( slug ) }`
			);
		} else {
			patternSlugs.set( slug, file );
		}
	}

	const referenceFiles = files.filter( ( file ) =>
		/\.(?:html|php)$/.test( file )
	);
	for ( const file of referenceFiles ) {
		const contents = await fs.readFile( file, 'utf8' );
		for ( const match of contents.matchAll(
			/<!--\s+wp:pattern\s+\{"slug":"([^"]+)"/g
		) ) {
			const slug = match[ 1 ];
			if (
				slug.startsWith( themeSlug + '/' ) &&
				! patternSlugs.has( slug )
			) {
				report( file, `references missing pattern ${ slug }` );
			}
		}
	}

	const assetReferences = [];
	for ( const file of files.filter( ( candidate ) =>
		/\.(?:css|html|js|json|php)$/.test( candidate )
	) ) {
		const contents = await fs.readFile( file, 'utf8' );
		for ( const match of contents.matchAll(
			/file:\.\/(assets\/[^"'\s)]+)/g
		) ) {
			assetReferences.push( { file, asset: match[ 1 ] } );
		}
		for ( const match of contents.matchAll( /\/(assets\/[^"'\s<)]+)/g ) ) {
			assetReferences.push( { file, asset: match[ 1 ] } );
		}
	}

	for ( const { file, asset } of assetReferences ) {
		const cleanAsset = asset.replace( /[?#].*$/, '' );
		try {
			await fs.access( path.join( themeDirectory, cleanAsset ) );
		} catch {
			report( file, `references missing asset ${ cleanAsset }` );
		}
	}

	return {
		errors,
		jsonFiles: jsonFiles.length,
		patterns: patternFiles.length,
		assets: assetReferences.length,
	};
}

if ( isMain( import.meta.url ) ) {
	try {
		const directory = await getThemeDirectory( process.argv[ 2 ] );
		const result = await validateTheme( directory );
		if ( result.errors.length ) {
			for ( const error of result.errors ) {
				console.error( error );
			}
			process.exitCode = 1;
		} else {
			console.log(
				`Validated ${ result.jsonFiles } JSON files, ${ result.patterns } patterns, and ${ result.assets } asset references.`
			);
		}
		const lintStatus = await lintPHP( directory );
		if ( lintStatus !== 0 ) {
			process.exitCode = lintStatus;
		}
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}
