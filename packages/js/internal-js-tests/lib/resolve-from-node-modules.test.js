const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const { resolveFromNodeModules } = require( './resolve-from-node-modules' );

const addPackage = ( root, name, version ) => {
	const dir = path.join( root, 'node_modules', name );
	fs.mkdirSync( dir, { recursive: true } );
	fs.writeFileSync(
		path.join( dir, 'package.json' ),
		JSON.stringify( { name, version, main: 'index.js' } )
	);
	fs.writeFileSync( path.join( dir, 'index.js' ), '' );
	return path.join( dir, 'index.js' );
};

describe( 'resolveFromNodeModules', () => {
	let root;

	beforeEach( () => {
		root = fs.mkdtempSync( path.join( os.tmpdir(), 'resolve-test-' ) );
	} );

	afterEach( () => {
		fs.rmSync( root, { recursive: true, force: true } );
	} );

	it( 'takes the nearest node_modules above the start directory', () => {
		addPackage( root, '@scope/pkg', '1.0.0' );
		const near = addPackage(
			path.join( root, 'a' ),
			'@scope/pkg',
			'2.0.0'
		);
		const start = path.join( root, 'a', 'b', 'c' );
		fs.mkdirSync( start, { recursive: true } );

		expect( resolveFromNodeModules( '@scope/pkg', [ start ] ) ).toBe(
			fs.realpathSync( near )
		);
	} );

	it( 'prefers the first start directory that has the package', () => {
		const project = path.join( root, 'project' );
		const tooling = path.join( root, 'tooling' );
		addPackage( project, '@scope/pkg', '1.0.0' );
		const fallback = addPackage( tooling, '@scope/pkg', '2.0.0' );
		fs.mkdirSync( path.join( root, 'other' ) );

		expect(
			resolveFromNodeModules( '@scope/pkg', [
				path.join( root, 'other' ),
				tooling,
				project,
			] )
		).toBe( fs.realpathSync( fallback ) );
	} );

	it( 'returns null when no chain has the package', () => {
		expect( resolveFromNodeModules( '@scope/missing', [ root ] ) ).toBe(
			null
		);
	} );

	it( 'ignores NODE_PATH', () => {
		const hoisted = path.join( root, 'hoisted' );
		addPackage( hoisted, '@scope/pkg', '9.9.9' );
		const start = path.join( root, 'project' );
		fs.mkdirSync( start );
		const script = `
			const { resolveFromNodeModules } = require( ${ JSON.stringify(
				require.resolve( './resolve-from-node-modules' )
			) } );
			const found = resolveFromNodeModules( '@scope/pkg', [ ${ JSON.stringify(
				start
			) } ] );
			let plain = null;
			try { plain = require.resolve( '@scope/pkg', { paths: [ ${ JSON.stringify(
				start
			) } ] } ); } catch ( e ) {}
			process.stdout.write( JSON.stringify( { found, plain } ) );
		`;
		const { found, plain } = JSON.parse(
			execFileSync( process.execPath, [ '-e', script ], {
				env: {
					...process.env,
					NODE_PATH: path.join( hoisted, 'node_modules' ),
				},
			} ).toString()
		);

		expect( plain ).toContain( 'hoisted' );
		expect( found ).toBe( null );
	} );
} );
