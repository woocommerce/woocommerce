const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs/promises' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const webpack = require( 'webpack' );
const includeScripts = require( '../src/webpack-text-includes' );
const minifyBundleCopies = require( '../src/webpack-dist-minify' );

test( 'Expands classic includes and emits a minified bundle copy', async ( context ) => {
	const directory = await fs.mkdtemp(
		path.join( os.tmpdir(), 'woo-extension-webpack-' )
	);
	context.after( () => fs.rm( directory, { recursive: true, force: true } ) );

	await fs.writeFile(
		path.join( directory, 'classic.js' ),
		"include( 'part.js' );\ninclude( 'part.js' );\n"
	);
	await fs.writeFile( path.join( directory, 'part.js' ), 'const part = 1;' );
	await fs.writeFile(
		path.join( directory, 'entry.js' ),
		"console.log( 'Hello world' );\n"
	);

	await new Promise( ( resolve, reject ) => {
		webpack(
			{
				context: directory,
				entry: './entry.js',
				mode: 'none',
				output: { path: directory, filename: 'bundle.js' },
				plugins: [
					includeScripts( [ [ 'classic.js', 'assembled.js' ] ] ),
					minifyBundleCopies( [ 'bundle.js' ] ),
				],
			},
			( error, stats ) => {
				if ( error ) {
					reject( error );
				} else if ( stats.hasErrors() ) {
					reject( new Error( stats.toString( 'errors-only' ) ) );
				} else {
					resolve();
				}
			}
		);
	} );

	const assembled = await fs.readFile(
		path.join( directory, 'assembled.js' ),
		'utf8'
	);
	assert.equal( assembled, 'const part = 1;\n\n' );

	const bundle = await fs.readFile(
		path.join( directory, 'bundle.js' ),
		'utf8'
	);
	const minified = await fs.readFile(
		path.join( directory, 'bundle.min.js' ),
		'utf8'
	);
	assert.ok( minified.length < bundle.length );
	assert.match( minified, /Hello world/ );
} );

async function buildIncludes( context, classic, plugins = [] ) {
	const directory = await fs.mkdtemp(
		path.join( os.tmpdir(), 'woo-extension-webpack-' )
	);
	context.after( () => fs.rm( directory, { recursive: true, force: true } ) );
	await fs.writeFile( path.join( directory, 'classic.js' ), classic );
	await fs.writeFile( path.join( directory, 'part.js' ), 'const part = 1;' );
	await fs.writeFile( path.join( directory, 'entry.js' ), '' );

	const stats = await new Promise( ( resolve, reject ) => {
		webpack(
			{
				context: directory,
				entry: './entry.js',
				mode: 'none',
				output: { path: directory, filename: 'bundle.js' },
				plugins: [
					includeScripts( [ [ 'classic.js', 'assembled.js' ] ] ),
					...plugins,
				],
			},
			( error, result ) => ( error ? reject( error ) : resolve( result ) )
		);
	} );
	return { directory, stats };
}

test( 'Expands includes written with other spacing and quotes', async ( context ) => {
	const { directory, stats } = await buildIncludes(
		context,
		"include('part.js');\n\tinclude( \"part.js\" )\ninclude ('part.js');\n"
	);

	assert.equal( stats.hasErrors(), false, stats.toString( 'errors-only' ) );
	assert.equal(
		await fs.readFile( path.join( directory, 'assembled.js' ), 'utf8' ),
		'const part = 1;\n\n\n'
	);
} );

test( 'Leaves the include copy when a bundle pattern also matches it', async ( context ) => {
	const { directory, stats } = await buildIncludes(
		context,
		"include( 'part.js' );\n",
		[ minifyBundleCopies( [ '*.js' ] ) ]
	);

	assert.equal( stats.hasErrors(), false, stats.toString( 'errors-only' ) );
	assert.equal(
		await fs.readFile( path.join( directory, 'assembled.min.js' ), 'utf8' ),
		'const part = 1;\n'
	);
	assert.ok( ( await fs.readdir( directory ) ).includes( 'bundle.min.js' ) );
} );

test( 'Fails the build on an include line it cannot expand', async ( context ) => {
	const { stats } = await buildIncludes(
		context,
		"include( 'part.js' ); // Shared part.\n"
	);

	assert.match(
		stats.toString( 'errors-only' ),
		/classic\.js:1: unsupported include\(\) line/
	);
} );
