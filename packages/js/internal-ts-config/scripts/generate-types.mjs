/**
 * Auto-generate ambient type declarations from node_modules.
 *
 * Transforms .d.ts files from real packages into script-mode
 * `declare module` blocks that ensure portable declaration emission
 * (no TS2742 errors from pnpm virtual store paths).
 *
 * Usage:
 *   node scripts/generate-types.mjs generate [--output=<dir>]
 *   node scripts/generate-types.mjs update-patch
 */

/* eslint-disable no-console */

import {
	readFileSync,
	writeFileSync,
	mkdirSync,
	rmSync,
	readdirSync,
	existsSync,
} from 'node:fs';
import { join, dirname, relative, resolve, posix } from 'node:path';
import { execSync } from 'node:child_process';
import { tmpdir } from 'node:os';
import { createRequire } from 'node:module';
import ts from 'typescript';

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

// Packages to generate ambient type declarations for.
// DT vs build-types is auto-detected from devDependencies in package.json:
// if @types/wordpress__<slug> exists → DT package (typesDir '.'),
// otherwise → the package's own build-types directory.
const PACKAGE_NAMES = [
	'@wordpress/block-editor',
	'@wordpress/core-data',
	'@wordpress/data',
	'@wordpress/editor',
	'@wordpress/notices',
];

const PKG_ROOT = resolve( dirname( new URL( import.meta.url ).pathname ), '..' );
const TYPES_DIR = join( PKG_ROOT, 'types' );

function dtPackageName( packageName ) {
	return '@types/' + packageName.slice( 1 ).replace( '/', '__' );
}

function resolvePackageConfigs() {
	const pkgJson = JSON.parse( readFileSync( join( PKG_ROOT, 'package.json' ), 'utf8' ) );
	const devDeps = pkgJson.devDependencies || {};

	return PACKAGE_NAMES.map( ( name ) => {
		const dtPkg = dtPackageName( name ) in devDeps;
		return {
			name,
			dtPackage: dtPkg,
			typesDir: dtPkg ? '.' : 'build-types',
			entryPoint: 'index.d.ts',
		};
	} );
}

const PACKAGES = resolvePackageConfigs();

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Resolve the real filesystem path to a package's types directory.
 *
 * @param {string}  packageName - e.g. '@wordpress/core-data'
 * @param {string}  typesDir    - e.g. 'build-types' or '.'
 * @param {boolean} dtPackage   - When true, resolve the DefinitelyTyped package instead.
 * @return {string} Absolute path to the types directory.
 */
function resolvePackageTypesDir( packageName, typesDir, dtPackage ) {
	const require = createRequire( join( PKG_ROOT, 'index.js' ) );

	if ( dtPackage ) {
		const pkgJsonPath = require.resolve( `${ dtPackageName( packageName ) }/package.json` );
		return dirname( pkgJsonPath );
	}

	const pkgJsonPath = require.resolve( `${ packageName }/package.json` );
	return join( dirname( pkgJsonPath ), typesDir );
}

function collectDtsFiles( dir ) {
	return new Set(
		readdirSync( dir, { recursive: true } )
			.filter( ( f ) => f.endsWith( '.d.ts' ) )
			.map( ( f ) => f.replaceAll( '\\', '/' ) )
	);
}

/**
 * Given a relative module specifier and the directory of the importing file
 * (relative to the typesDir root), resolve the target file path.
 *
 * @param {string}      specifier  - e.g. './helpers', '../index'
 * @param {string}      fromDir    - Directory of the importing file, relative to typesDir root.
 * @param {Set<string>} allFiles   - All .d.ts file paths relative to typesDir root.
 * @return {{ filePath: string, isIndex: boolean } | null}
 */
function resolveRelativeSpecifier( specifier, fromDir, allFiles ) {
	// Resolve to a path relative to the typesDir root.
	const resolved = posix.normalize(
		fromDir ? `${ fromDir }/${ specifier }` : specifier
	);

	// Try exact file match first.
	const candidates = [
		`${ resolved }.d.ts`,
		`${ resolved }/index.d.ts`,
	];

	for ( const raw of candidates ) {
		const candidate = posix.normalize( raw );
		if ( allFiles.has( candidate ) ) {
			return {
				filePath: candidate,
				isIndex: candidate.endsWith( '/index.d.ts' ),
			};
		}
	}

	return null;
}

/**
 * Compute the module specifier for a given file path.
 *
 * @param {string} filePath       - Path relative to typesDir root (e.g. 'entity-types/helpers.d.ts').
 * @param {string} packageName    - e.g. '@wordpress/core-data'
 * @param {string} typesDir       - e.g. 'build-types'
 * @param {string} entryPointFile - e.g. 'index.d.ts'
 * @return {string} Module specifier (e.g. '@wordpress/core-data/build-types/entity-types/helpers').
 */
function computeModuleSpecifier( filePath, packageName, typesDir, entryPointFile ) {
	if ( filePath === entryPointFile ) {
		// Entry point gets the bare package name.
		return packageName;
	}

	// Strip .d.ts extension.
	let specPath = filePath.replace( /\.d\.ts$/, '' );

	// Strip /index suffix for directory modules.
	specPath = specPath.replace( /\/index$/, '' );

	// When typesDir is '.', the types live at the package root —
	// don't insert a redundant './' segment.
	if ( typesDir === '.' ) {
		return `${ packageName }/${ specPath }`;
	}

	return `${ packageName }/${ typesDir }/${ specPath }`;
}

function computeOutputPath( filePath, packageName ) {
	return `${ packageName }/${ filePath }`;
}

/**
 * Walk the TypeScript AST and collect all relative module specifiers
 * along with their positions in the source text.
 *
 * @param {ts.SourceFile} sourceFile - Parsed source file.
 * @return {Array<{ specifier: string, start: number, end: number }>}
 */
function collectRelativeSpecifiers( sourceFile ) {
	const results = [];

	function visit( node ) {
		// ImportDeclaration: import { X } from './helpers'
		// ExportDeclaration: export { X } from './helpers', export * from './helpers'
		if (
			( ts.isImportDeclaration( node ) || ts.isExportDeclaration( node ) ) &&
			node.moduleSpecifier &&
			ts.isStringLiteral( node.moduleSpecifier )
		) {
			const spec = node.moduleSpecifier.text;
			if ( spec.startsWith( '.' ) ) {
				results.push( {
					specifier: spec,
					// +1/-1 to skip the quote characters in the source text.
					start: node.moduleSpecifier.getStart( sourceFile ) + 1,
					end: node.moduleSpecifier.getEnd() - 1,
				} );
			}
		}

		// ImportTypeNode: import('./helpers').SomeType
		if ( ts.isImportTypeNode( node ) && ts.isLiteralTypeNode( node.argument ) ) {
			const literal = node.argument.literal;
			if ( ts.isStringLiteral( literal ) && literal.text.startsWith( '.' ) ) {
				results.push( {
					specifier: literal.text,
					start: literal.getStart( sourceFile ) + 1,
					end: literal.getEnd() - 1,
				} );
			}
		}

		// ModuleDeclaration: declare module './foo' { ... }
		// Nested declare module blocks with relative specifiers must be
		// rewritten to absolute module specifiers so that the augmentation
		// targets the correct module. Without rewriting, the relative path
		// resolves against the file on disk (e.g. types/@wordpress/core-data/...)
		// instead of the declared module specifier (@wordpress/core-data/build-types/...).
		if (
			ts.isModuleDeclaration( node ) &&
			ts.isStringLiteral( node.name ) &&
			node.name.text.startsWith( '.' )
		) {
			results.push( {
				specifier: node.name.text,
				start: node.name.getStart( sourceFile ) + 1,
				end: node.name.getEnd() - 1,
			} );
		}

		ts.forEachChild( node, visit );
	}

	visit( sourceFile );
	return results;
}

// ---------------------------------------------------------------------------
// Core Transform
// ---------------------------------------------------------------------------

/**
 * Transform a single .d.ts file into a declare module block.
 *
 * @param {Object} params
 * @param {string} params.sourceText  - Original .d.ts source text.
 * @param {string} params.filePath    - Path relative to typesDir root.
 * @param {string} params.packageName - e.g. '@wordpress/core-data'
 * @param {string} params.typesDir    - e.g. 'build-types'
 * @param {string} params.entryPoint  - e.g. 'index.d.ts'
 * @param {Set<string>} params.allFiles  - All .d.ts file paths in the package.
 * @return {string} Transformed source text.
 */
function transformFile( { sourceText, filePath, packageName, typesDir, entryPoint, allFiles } ) {
	const moduleSpecifier = computeModuleSpecifier( filePath, packageName, typesDir, entryPoint );
	const fileDir = posix.dirname( filePath );

	// Parse the source for AST analysis.
	const sourceFile = ts.createSourceFile(
		filePath,
		sourceText,
		ts.ScriptTarget.Latest,
		true,
		ts.ScriptKind.TS
	);

	// Collect relative specifiers from the AST.
	const relativeSpecifiers = collectRelativeSpecifiers( sourceFile );

	// Build the set of same-package dependencies (for reference directives).
	const referencedFiles = new Set();

	// Sort replacements by position (descending) to replace from end to start.
	const replacements = [];

	for ( const { specifier, start, end } of relativeSpecifiers ) {
		const resolved = resolveRelativeSpecifier( specifier, fileDir, allFiles );
		if ( ! resolved ) {
			// Not a same-package reference — leave it as-is.
			continue;
		}

		const targetModuleSpecifier = computeModuleSpecifier(
			resolved.filePath,
			packageName,
			typesDir,
			entryPoint
		);

		replacements.push( { start, end, replacement: targetModuleSpecifier } );
		referencedFiles.add( resolved.filePath );
	}

	// Apply replacements from end to start to preserve positions.
	let modified = sourceText;
	replacements.sort( ( a, b ) => b.start - a.start );
	for ( const { start, end, replacement } of replacements ) {
		modified = modified.slice( 0, start ) + replacement + modified.slice( end );
	}

	// Strip source map comments.
	modified = modified.replace( /\/\/# sourceMappingURL=.*$/gm, '' );

	// Extract and remove triple-slash reference-types directives
	// (they must appear outside the declare module block).
	const referenceTypesDirectives = [];
	modified = modified.replace(
		/\/\/\/ <reference types="([^"]+)" \/>\s*\n?/g,
		( _match, typesName ) => {
			referenceTypesDirectives.push( typesName );
			return '';
		}
	);

	// Extract and remove triple-slash reference-path directives.
	// These are direct file paths (already have .d.ts extension), so resolve
	// them relative to the current file and add to referencedFiles for
	// correct top-level directive generation.
	modified = modified.replace(
		/\/\/\/ <reference path="([^"]+)" \/>\s*\n?/g,
		( _match, refPath ) => {
			const candidate = posix.normalize(
				fileDir ? `${ fileDir }/${ refPath }` : refPath
			);
			if ( allFiles.has( candidate ) ) {
				referencedFiles.add( candidate );
			}
			return '';
		}
	);

	// Trim trailing whitespace and indent the body by one tab (single pass).
	const indented = modified
		.split( '\n' )
		.map( ( line ) => {
			const trimmed = line.trimEnd();
			return trimmed === '' ? '' : `\t${ trimmed }`;
		} )
		.join( '\n' )
		.trimEnd();

	// Build reference path directives for same-package dependencies.
	const outputPath = computeOutputPath( filePath, packageName );
	const outputDir = posix.dirname( outputPath );
	const referencePathDirectives = [];

	for ( const refFile of [ ...referencedFiles ].sort() ) {
		const refOutputPath = computeOutputPath( refFile, packageName );
		let relRef = posix.relative( outputDir, refOutputPath );
		if ( ! relRef.startsWith( '.' ) ) {
			relRef = `./${ relRef }`;
		}
		referencePathDirectives.push( relRef );
	}

	// Assemble the output.
	const parts = [];

	for ( const typesName of referenceTypesDirectives ) {
		parts.push( `/// <reference types="${ typesName }" />` );
	}

	for ( const refPath of referencePathDirectives ) {
		parts.push( `/// <reference path="${ refPath }" />` );
	}

	if ( parts.length > 0 ) {
		parts.push( '' ); // Blank line after directives.
	}

	parts.push( `declare module '${ moduleSpecifier }' {` );
	parts.push( indented );
	parts.push( '}' );
	parts.push( '' ); // Trailing newline.

	return parts.join( '\n' );
}

// ---------------------------------------------------------------------------
// Generate Command
// ---------------------------------------------------------------------------

function generate( outputDir ) {
	for ( const pkg of PACKAGES ) {
		let sourceDir;
		try {
			sourceDir = resolvePackageTypesDir( pkg.name, pkg.typesDir, pkg.dtPackage );
		} catch {
			console.warn( `Skipping ${ pkg.name }: package not found` );
			continue;
		}
		const allFiles = collectDtsFiles( sourceDir );
		const pkgOutputDir = join( outputDir, pkg.name );

		// Clean output directory.
		if ( existsSync( pkgOutputDir ) ) {
			rmSync( pkgOutputDir, { recursive: true } );
		}

		for ( const filePath of allFiles ) {
			const sourceText = readFileSync( join( sourceDir, filePath ), 'utf8' );

			const transformed = transformFile( {
				sourceText,
				filePath,
				packageName: pkg.name,
				typesDir: pkg.typesDir,
				entryPoint: pkg.entryPoint,
				allFiles,
			} );

			const outPath = join( pkgOutputDir, filePath );
			mkdirSync( dirname( outPath ), { recursive: true } );
			writeFileSync( outPath, transformed );
		}

		console.log( `Generated types for ${ pkg.name } (${ allFiles.size } files)` );
	}
}

// ---------------------------------------------------------------------------
// Patch Workflow
// ---------------------------------------------------------------------------

/**
 * Apply a patch file if it exists.
 *
 * Patch paths are relative to PKG_ROOT (e.g. `types/@wordpress/core-data/index.d.ts`),
 * so `git apply` runs from PKG_ROOT without `--directory`.
 *
 * @param {string} patchFile - Path to the patch file.
 */
function applyPatch( patchFile ) {
	if ( ! existsSync( patchFile ) ) {
		return;
	}

	try {
		// Double `git apply` to gracefully handle older versions of git.
		execSync(
			`git apply --allow-empty "${ patchFile }" || git apply "${ patchFile }"`,
			{ cwd: PKG_ROOT, stdio: 'pipe' }
		);
		console.log( `Applied patch: ${ relative( PKG_ROOT, patchFile ) }` );
	} catch ( error ) {
		console.error(
			`Failed to apply patch ${ relative( PKG_ROOT, patchFile ) }:`,
			error.stderr?.toString() || error.message
		);
		process.exit( 1 );
	}
}

function runGenerate( outputDir ) {
	const targetDir = outputDir || TYPES_DIR;
	generate( targetDir );

	// Apply patches (only when writing to the default TYPES_DIR,
	// since patch paths are relative to PKG_ROOT).
	if ( ! outputDir ) {
		for ( const pkg of PACKAGES ) {
			const patchFile = join( TYPES_DIR, `${ pkg.name }.patch` );
			applyPatch( patchFile );
		}
	}
}

function runUpdatePatch() {
	const cleanDir = join( tmpdir(), `wc-generate-types-${ Date.now() }` );

	try {
		mkdirSync( cleanDir, { recursive: true } );

		// Generate clean (unpatched) output to temp directory.
		generate( cleanDir );

		for ( const pkg of PACKAGES ) {
			const patchFile = join( TYPES_DIR, `${ pkg.name }.patch` );
			const cleanPkgDir = join( cleanDir, pkg.name );
			const currentPkgDir = join( TYPES_DIR, pkg.name );

			if ( ! existsSync( currentPkgDir ) ) {
				console.log( `No current types for ${ pkg.name }, skipping patch update.` );
				continue;
			}

			try {
				const diff = execSync(
					`diff -ruN "${ cleanPkgDir }" "${ currentPkgDir }"`,
					{ encoding: 'utf8' }
				);
				// diff returns 0 when files are identical — no patch needed.
				if ( existsSync( patchFile ) ) {
					rmSync( patchFile );
					console.log( `Removed empty patch: ${ relative( PKG_ROOT, patchFile ) }` );
				}
			} catch ( error ) {
				if ( error.status === 1 && error.stdout ) {
					// diff returns 1 when files differ — this is our patch content.
					// Rewrite paths so the patch applies from the package root.
					const patchContent = error.stdout
						.replaceAll( cleanPkgDir, `a/types/${ pkg.name }` )
						.replaceAll( currentPkgDir, `b/types/${ pkg.name }` )
						// Strip timestamps from diff headers to avoid noisy diffs.
						.replace( /^(---\s+\S+)\t.+$/gm, '$1' )
						.replace( /^(\+\+\+\s+\S+)\t.+$/gm, '$1' );
					writeFileSync( patchFile, patchContent );
					console.log( `Updated patch: ${ relative( PKG_ROOT, patchFile ) }` );
				} else {
					throw error;
				}
			}
		}
	} finally {
		rmSync( cleanDir, { recursive: true, force: true } );
	}
}

// ---------------------------------------------------------------------------
// CLI
// ---------------------------------------------------------------------------

const args = process.argv.slice( 2 );
const command = args[ 0 ];

switch ( command ) {
	case 'generate': {
		const outputArg = args.find( ( a ) => a.startsWith( '--output=' ) );
		const outputDir = outputArg ? resolve( outputArg.slice( '--output='.length ) ) : undefined;
		runGenerate( outputDir );
		break;
	}
	case 'update-patch':
		runUpdatePatch();
		break;
	default:
		console.error( 'Usage: generate-types.mjs <generate|update-patch> [--output=<dir>]' );
		process.exit( 1 );
}
