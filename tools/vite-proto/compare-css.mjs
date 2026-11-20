// Compare stylesheets with the control: same bytes, same modules in another
// order, or different content.
import fs from 'node:fs';
import path from 'node:path';
const [ control, candidate ] = process.argv.slice( 2 );
const list = ( dir, out = [], base = dir ) => {
	for ( const e of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const p = path.join( dir, e.name );
		if ( e.isDirectory() ) list( p, out, base );
		else if ( p.endsWith( '.css' ) ) out.push( path.relative( base, p ) );
	}
	return out;
};
const rules = ( css ) => css.split( '}' ).map( ( r ) => r.trim() ).filter( Boolean ).sort().join( '}' );
let identical = 0, reordered = 0, sameRules = 0;
const different = [];
for ( const f of list( control ).sort() ) {
	const b = path.join( candidate, f );
	if ( ! fs.existsSync( b ) ) continue;
	const x = fs.readFileSync( path.join( control, f ), 'utf8' );
	const y = fs.readFileSync( b, 'utf8' );
	if ( x === y ) identical++;
	else if ( x.split( '\n' ).sort().join( '\n' ) === y.split( '\n' ).sort().join( '\n' ) ) reordered++;
	else if ( rules( x ) === rules( y ) ) sameRules++;
	else {
		const xl = new Set( x.split( '\n' ) ), yl = new Set( y.split( '\n' ) );
		different.push( { f, control: x.length, candidate: y.length,
			modulesOnlyControl: [ ...xl ].filter( ( l ) => ! yl.has( l ) ).length,
			modulesOnlyCandidate: [ ...yl ].filter( ( l ) => ! xl.has( l ) ).length } );
	}
}
console.log( JSON.stringify( { identical, reordered, sameRulesOtherOrder: sameRules, different: different.length } ) );
if ( process.env.VERBOSE ) for ( const d of different ) console.log( JSON.stringify( d ) );
