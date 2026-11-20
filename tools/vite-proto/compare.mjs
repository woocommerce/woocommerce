// Compare a build folder with the control: file set, .asset.php lists, sizes.
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const [ control, candidate, filterArg ] = process.argv.slice( 2 );
const filter = filterArg ? new RegExp( filterArg ) : null;
const list = ( dir ) => {
	const out = [];
	const walk = ( d ) => {
		for ( const e of fs.readdirSync( d, { withFileTypes: true } ) ) {
			const p = path.join( d, e.name );
			if ( e.isDirectory() ) walk( p );
			else out.push( path.relative( dir, p ) );
		}
	};
	walk( dir );
	return out.filter( ( f ) => ! f.endsWith( '.LICENSE.txt' ) && ( ! filter || filter.test( f ) ) ).sort();
};
const a = list( control );
const b = list( candidate );
const setB = new Set( b );
const setA = new Set( a );
const onlyControl = a.filter( ( f ) => ! setB.has( f ) );
const onlyCandidate = b.filter( ( f ) => ! setA.has( f ) );
const deps = ( file ) => {
	const text = fs.readFileSync( file, 'utf8' );
	const m = /'dependencies' => array\(([^)]*)\)/.exec( text );
	return m ? m[ 1 ].split( ',' ).map( ( s ) => s.trim().replace( /'/g, '' ) ).filter( Boolean ) : null;
};
let same = 0;
const different = [];
for ( const f of a.filter( ( x ) => x.endsWith( '.asset.php' ) && setB.has( x ) ) ) {
	const da = deps( path.join( control, f ) );
	const db = deps( path.join( candidate, f ) );
	if ( ! da || ! db ) continue;
	const missing = da.filter( ( d ) => ! db.includes( d ) );
	const extra = db.filter( ( d ) => ! da.includes( d ) );
	const order = ! missing.length && ! extra.length && da.join() !== db.join();
	if ( missing.length || extra.length || order ) different.push( { f, missing, extra, order } );
	else same++;
}
const size = ( dir, files, ext ) => {
	let raw = 0, gz = 0, n = 0;
	for ( const f of files.filter( ( x ) => x.endsWith( ext ) ) ) {
		const buf = fs.readFileSync( path.join( dir, f ) );
		raw += buf.length; gz += zlib.gzipSync( buf, { level: 9 } ).length; n++;
	}
	return { n, rawKB: Math.round( raw / 1024 ), gzipKB: Math.round( gz / 1024 ) };
};
console.log( JSON.stringify( {
	files: { control: a.length, candidate: b.length, onlyControl: onlyControl.length, onlyCandidate: onlyCandidate.length },
	assetPhp: { same, different: different.length },
	js: { control: size( control, a, '.js' ), candidate: size( candidate, b, '.js' ) },
	css: { control: size( control, a, '.css' ), candidate: size( candidate, b, '.css' ) },
} ) );
if ( process.env.VERBOSE ) {
	console.log( 'only control:', onlyControl.join( ' ' ) );
	console.log( 'only candidate:', onlyCandidate.join( ' ' ) );
	for ( const d of different ) console.log( d.f, 'missing:', d.missing.join( ',' ), 'extra:', d.extra.join( ',' ), d.order ? 'ORDER' : '' );
}
