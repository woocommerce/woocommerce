// Bundle report, like webpack-bundle-analyzer's static report: one HTML page
// listing each written script and the modules inside it, biggest first.
import fs from 'node:fs';
import path from 'node:path';
import { analysis } from './classic.mjs';

const escape = ( text ) =>
	String( text ).replace( /[&<>"]/g, ( c ) => `&#${ c.charCodeAt( 0 ) };` );
const kb = ( bytes ) => `${ ( bytes / 1024 ).toFixed( 1 ) } KB`;

export function writeBundleReport( outDir, title ) {
	if ( ! analysis.length ) {
		return null;
	}
	const scripts = [ ...analysis ].sort( ( a, b ) => b.bytes - a.bytes );
	const rows = scripts
		.map( ( script ) => {
			const modules = Object.entries( script.modules )
				.sort( ( a, b ) => b[ 1 ] - a[ 1 ] )
				.map(
					( [ id, bytes ] ) =>
						`<tr><td>${ escape( id ) }</td><td>${ kb( bytes ) }</td></tr>`
				)
				.join( '' );
			return `<details><summary>${ escape( script.out ) } — ${ kb( script.bytes ) } (${ escape(
				script.build
			) }, ${ Object.keys( script.modules ).length } modules)</summary><table>${ modules }</table></details>`;
		} )
		.join( '\n' );
	const html = `<!doctype html><meta charset="utf-8"><title>${ escape( title ) }</title>
<style>body{font:14px system-ui;margin:2em}summary{cursor:pointer;padding:2px 0}table{margin:0 0 1em 2em;border-collapse:collapse}td{padding:1px 8px}td:last-child{text-align:right}</style>
<h1>${ escape( title ) }</h1><p>Sizes are minified, before gzip. Module sizes are Rolldown's rendered lengths.</p>
${ rows }`;
	const file = path.join( outDir, 'bundle-report.html' );
	fs.writeFileSync( file, html );
	fs.writeFileSync(
		path.join( outDir, 'bundle-report.json' ),
		JSON.stringify( scripts )
	);
	return file;
}
