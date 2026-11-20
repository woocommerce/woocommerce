/**
 * Classic-script builds with Rolldown.
 *
 * Rolldown cannot split IIFE output, and ES modules load deferred, which
 * breaks extensions that read `wc.*` globals. So each webpack config becomes
 * one Rolldown build with CommonJS output, and every chunk is wrapped as a
 * factory in a small registry (one registry per config, like webpack's
 * `uniqueName`). Files that PHP enqueues stay classic scripts:
 *
 * - An entry file holds its own factory plus the factories of the unnamed
 *   shared chunks it needs, so PHP needs no new handles. A factory that is
 *   already defined is skipped, so shared code still runs once per page.
 * - Named shared chunks (wc-blocks-vendors and so on) are their own files
 *   with the names PHP registers.
 * - Lazy chunks are their own files, loaded by a script tag.
 *
 * The linker leaves Rolldown's code as it is. The registry resolves each
 * `require()` at run time: a path to another chunk, or `wc-g:wp.data` for
 * the `window.wp.data` global. Everything else the linker needs comes from
 * Rolldown's chunk metadata.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { rolldown, watch } from 'rolldown';
import { parseAst, parseAstAsync } from 'rolldown/parseAst';
import { parseSync } from 'rolldown/utils';
import MagicString, { Bundle } from 'magic-string';
import remapping from '@jridgewell/remapping';
import { styleMark, isStyle } from './css.mjs';
import { propTypesPlugin } from './prop-types.mjs';

// An external becomes `require( 'wc-g:wp.data' )`; the runtime reads the global.
const EXTERNAL = 'wc-g:';
const ENTRY = '\0wc-entry:';
const ENTRY_FILE = 'wc-entry-file:';
const I18N_FUNCTIONS = new Set( [ '__', '_x', '_n', '_nx' ] );
const TRANSLATORS =
	/\/\*\s*(translators:[\s\S]*?)\*\/|\/\/\s*(translators:[^\n]*)/gi;

const hash = ( content, length = 20 ) =>
	createHash( 'sha1' ).update( content ).digest( 'hex' ).slice( 0, length );

const globalExpression = ( external ) =>
	'window' +
	( Array.isArray( external ) ? external : [ external ] )
		.map( ( part ) => `[${ JSON.stringify( part ) }]` )
		.join( '' );

export const assetPhp = ( data ) => {
	const parts = [];
	if ( data.dependencies ) {
		parts.push(
			`'dependencies' => array(${ data.dependencies
				.map( ( d ) => `'${ d }'` )
				.join( ', ' ) })`
		);
	}
	parts.push( `'version' => '${ data.version }'` );
	return `<?php return array(${ parts.join( ', ' ) });\n`;
};

function walk( node, visit ) {
	if ( ! node || typeof node !== 'object' ) {
		return;
	}
	if ( Array.isArray( node ) ) {
		for ( const child of node ) {
			walk( child, visit );
		}
		return;
	}
	if ( typeof node.type === 'string' ) {
		visit( node );
	}
	for ( const key in node ) {
		const value = node[ key ];
		if ( value && typeof value === 'object' ) {
			walk( value, visit );
		}
	}
}

const langOf = ( id ) => {
	const ext = path.extname( id.split( '?' )[ 0 ] );
	if ( ext === '.ts' ) {
		return 'ts';
	}
	return ext === '.tsx' ? 'tsx' : 'jsx';
};

/**
 * Wrap translator comments and dynamic imports so they survive minification.
 *
 * `__wcT( 'translators: …', __( … ) )` is turned back into a comment right
 * before the call after minifying. `__wcLazy( key, () => import( … ) )` lets
 * the runtime load the chunk file before the import runs.
 */
async function rewriteSource( ctx, code, id, config, state ) {
	const wantsLazy = config.format !== 'esm' && code.includes( 'import(' );
	const wantsTranslators =
		/translators:/i.test( code ) && ! id.includes( 'node_modules' );
	if ( ! wantsLazy && ! wantsTranslators ) {
		return null;
	}
	let ast;
	try {
		ast = parseAst( code, { lang: langOf( id ) } );
	} catch {
		return null;
	}
	const imports = [];
	const calls = [];
	walk( ast, ( node ) => {
		if ( node.type === 'ImportExpression' ) {
			imports.push( node );
		} else if (
			node.type === 'CallExpression' &&
			node.callee.type === 'Identifier' &&
			I18N_FUNCTIONS.has( node.callee.name )
		) {
			calls.push( node );
		}
	} );
	const edits = [];
	if ( wantsLazy ) {
		for ( const node of imports ) {
			const source = node.source;
			if (
				source.type !== 'Literal' ||
				typeof source.value !== 'string'
			) {
				continue;
			}
			const resolved = await ctx.resolve( source.value, id );
			if ( ! resolved || resolved.external ) {
				continue;
			}
			const named = /webpackChunkName:\s*["']([^"']+)["']/.exec(
				code.slice( node.start, source.start )
			);
			const key = named
				? named[ 1 ]
				: hash( path.relative( config.root, resolved.id ), 8 );
			state.lazyNames.set( key, Boolean( named ) );
			state.lazyModules.set( key, resolved.id );
			if ( ! state.lazyKeysByModule.has( id ) ) {
				state.lazyKeysByModule.set( id, new Set() );
			}
			state.lazyKeysByModule.get( id ).add( key );
			edits.push( [
				node.start,
				`__wcLazy(${ JSON.stringify( key ) }, () => `,
			] );
			edits.push( [ node.end, ')' ] );
		}
	}
	if ( wantsTranslators && calls.length ) {
		calls.sort( ( a, b ) => a.start - b.start );
		const byCall = new Map();
		for ( const match of code.matchAll( TRANSLATORS ) ) {
			const end = match.index + match[ 0 ].length;
			const call = calls.find( ( c ) => c.start >= end );
			if ( call ) {
				byCall.set(
					call,
					( match[ 1 ] || match[ 2 ] ).replace( /\s+/g, ' ' ).trim()
				);
			}
		}
		for ( const [ call, text ] of byCall ) {
			edits.push( [ call.start, `__wcT(${ JSON.stringify( text ) }, ` ] );
			edits.push( [ call.end, ')' ] );
		}
	}
	if ( ! edits.length ) {
		return null;
	}
	// Later positions first. At one position, closing brackets go before
	// opening wrappers, which the stable sort keeps in insertion order.
	edits
		.map( ( edit, index ) => [ ...edit, index ] )
		.sort( ( a, b ) => b[ 0 ] - a[ 0 ] || b[ 2 ] - a[ 2 ] )
		.forEach( ( [ position, text ] ) => {
			code = code.slice( 0, position ) + text + code.slice( position );
		} );
	return { code, map: null };
}

export function classicPlugin( config, state ) {
	const entryByName = new Map( config.entries.map( ( e ) => [ e.name, e ] ) );
	return {
		name: 'wc-classic',
		resolveId: {
			filter: { id: /^[^./\0]/ },
			handler( source ) {
				if ( source.startsWith( 'wc-entry:' ) ) {
					return '\0' + source;
				}
				if ( source.startsWith( ENTRY_FILE ) ) {
					// Webpack always runs an entry module, whatever the
					// package's sideEffects list says.
					return this.resolve(
						source.slice( ENTRY_FILE.length ),
						undefined,
						{
							skipSelf: true,
						}
					).then( ( resolved ) => ( {
						...resolved,
						moduleSideEffects: true,
					} ) );
				}
				const external = config.toGlobal( source );
				if ( ! external ) {
					return null;
				}
				if ( config.format === 'esm' ) {
					return { id: external, external: true };
				}
				if ( path.isAbsolute( source ) ) {
					return null;
				}
				// Kept external so Rolldown lists it per chunk. The id names
				// the global, which the runtime reads when the require runs.
				const externalId = EXTERNAL + [].concat( external ).join( '.' );
				if ( ! state.externalRequests.has( externalId ) ) {
					state.externalRequests.set( externalId, new Set() );
				}
				state.externalRequests.get( externalId ).add( source );
				return {
					id: externalId,
					external: true,
					moduleSideEffects: false,
				};
			},
		},
		load: {
			filter: { id: /^\0wc-/ },
			handler( id ) {
				if ( id.startsWith( ENTRY ) ) {
					const entry = entryByName.get( id.slice( ENTRY.length ) );
					const files = entry.files.map( ( f ) =>
						JSON.stringify(
							ENTRY_FILE + path.resolve( config.cwd, f )
						)
					);
					const lines = files
						.slice( 0, -1 )
						.map( ( f ) => `import ${ f };` );
					if ( entry.library ) {
						const [ first, ...rest ] = entry.library;
						let target = `(window[${ JSON.stringify(
							first
						) }] = window[${ JSON.stringify( first ) }] || {})`;
						for ( const part of rest.slice( 0, -1 ) ) {
							target = `(${ target }[${ JSON.stringify(
								part
							) }] = ${ target }[${ JSON.stringify( part ) }] || {})`;
						}
						lines.push(
							`import * as entryExports from ${ files.at( -1 ) };`,
							`${ target }[${ JSON.stringify(
								rest.at( -1 )
							) }] = entryExports;`
						);
					} else {
						lines.push( `import ${ files.at( -1 ) };` );
					}
					return { code: lines.join( '\n' ), moduleType: 'js' };
				}
				return null;
			},
		},
		transform: {
			filter: {
				id: /\.[jt]sx?$/,
				code: /translators:|import\(/i,
			},
			handler( code, id ) {
				return rewriteSource( this, code, id, config, state );
			},
		},
		async generateBundle( _options, bundle ) {
			const started = performance.now();
			await link( this, bundle, config, state );
			for ( const file of Object.keys( bundle ) ) {
				delete bundle[ file ];
			}
			state.linkMs = Math.round( performance.now() - started );
		},
	};
}

/**
 * What the linker needs from one Rolldown chunk. Most of it comes from
 * Rolldown's chunk metadata; the code itself stays as Rolldown wrote it, and
 * the runtime resolves `require()` calls to other chunks and to externals.
 *
 * - `css`: the style marks of the style modules in the chunk.
 * - `lazy`: lazy chunk key to the chunk that holds the lazily loaded module.
 * - `externals`: externals the chunk's code requires, including requires in
 *   bundled CommonJS code, which Rolldown does not list as chunk imports.
 * - `__wcT( 'translators: …', call )` becomes `( /* translators: … *\/ call )`,
 *   edited on the parsed code so strings that look alike stay untouched.
 */
async function analyzeChunk( chunk, chunkOfModule, state, licenses ) {
	const modules = Object.keys( chunk.modules );
	const css = modules.filter( isStyle ).map( styleMark );
	const lazy = new Map();
	for ( const id of modules ) {
		for ( const key of state.lazyKeysByModule.get( id ) || [] ) {
			const target = chunkOfModule.get( state.lazyModules.get( key ) );
			if ( target ) {
				lazy.set( key, target );
			}
		}
	}
	const externals = new Set();
	for ( const match of chunk.code.matchAll( /["'`](wc-g:[\w$.]+)["'`]/g ) ) {
		externals.add( match[ 1 ] );
	}
	const ms = new MagicString( chunk.code );
	// License comments move to a .LICENSE.txt file, like webpack's Terser
	// plugin does. Only the parser knows which `/*!` text is a comment.
	const legal = [];
	if ( licenses && /\/[*/]!|@preserve|@license|@cc_on/.test( chunk.code ) ) {
		for ( const comment of parseSync( chunk.fileName, chunk.code )
			.comments ) {
			const text = chunk.code.slice( comment.start, comment.end );
			if ( LEGAL.test( text ) ) {
				legal.push(
					comment.type === 'Line' ? `/*${ comment.value } */` : text
				);
				const end =
					chunk.code[ comment.end ] === '\n'
						? comment.end + 1
						: comment.end;
				ms.remove( comment.start, end );
			}
		}
	}
	if ( chunk.code.includes( '__wcT(' ) ) {
		const ast = await parseAstAsync( chunk.code, { lang: 'js' } );
		walk( ast, ( node ) => {
			if (
				node.type !== 'CallExpression' ||
				node.callee.type !== 'Identifier' ||
				node.callee.name !== '__wcT'
			) {
				return;
			}
			const [ comment, call ] = node.arguments;
			const text = stringOf( comment );
			if ( text !== null && call ) {
				ms.overwrite(
					node.start,
					call.start,
					`(/* ${ text.replace( /\*\//g, '* /' ) } */`
				);
			}
		} );
	}
	// Shorter form of the export getters Rolldown writes. Only size depends
	// on this: if Rolldown writes them differently, nothing matches and the
	// getters stay as they are.
	let exportHelper = false;
	for ( const match of chunk.code.matchAll( EXPORT_GETTER ) ) {
		exportHelper = true;
		ms.overwrite(
			match.index,
			match.index + match[ 0 ].length,
			`__wcX(${ match[ 1 ] },()=>${ match[ 2 ] })`
		);
	}
	return {
		ms,
		code: ms.toString(),
		css,
		lazy,
		externals,
		exportHelper,
		legal,
	};
}

// Like DependencyExtractionWebpackPlugin's combineAssets: one assets.json
// file instead of an .asset.php file per script, for `build:check-assets`.
const ASSET_CHECK = process.env.ASSET_CHECK === 'true';

// Like WP_BUNDLE_ANALYZER in the webpack builds: build-*.mjs writes a
// report of what each script holds, from `analysis`.
const ANALYZE = Boolean(
	process.env.WP_BUNDLE_ANALYZER || process.env.ANALYZE
);
export const analysis = [];

// Terser's rule for license comments.
const LEGAL = /^\/[*/]!|@preserve|@license|@cc_on/im;

const EXPORT_GETTER =
	/Object\.defineProperty\(exports,\s*("[^"\\]+"),\s*\{\s*enumerable:\s*(?:!0|true),\s*get:\s*function\s*\(\)\s*\{\s*return ([\w$]+);?\s*\}\s*\}\)/g;

// The minifier writes many strings as template literals.
function stringOf( node ) {
	if ( node?.type === 'Literal' && typeof node.value === 'string' ) {
		return node.value;
	}
	if ( node?.type === 'TemplateLiteral' && node.expressions.length === 0 ) {
		return node.quasis[ 0 ].value.cooked;
	}
	return null;
}

/**
 * Turn Rolldown's chunks into the files PHP expects.
 */
async function link( ctx, bundle, config, state ) {
	const chunks = Object.values( bundle ).filter(
		( o ) => o.type === 'chunk'
	);
	const byFile = new Map( chunks.map( ( c ) => [ c.fileName, c ] ) );
	const entryByName = new Map( config.entries.map( ( e ) => [ e.name, e ] ) );
	const files = new Map();
	const emit = ( file, content ) => files.set( file, content );

	// Rewrite each chunk's code: registry ids, translator comments, CSS marks.
	const info = new Map();
	const chunkOfModule = new Map();
	for ( const chunk of chunks ) {
		for ( const id of Object.keys( chunk.modules ) ) {
			chunkOfModule.set( id, chunk.fileName );
		}
	}
	const rewritten = await Promise.all(
		chunks.map( ( chunk ) =>
			analyzeChunk(
				chunk,
				chunkOfModule,
				state,
				config.licenses && config.mode !== 'development'
			)
		)
	);
	chunks.forEach( ( chunk, index ) => {
		let kind = 'common';
		let entry = null;
		let named = null;
		const firstModule = Object.keys( chunk.modules ).find(
			( id ) => ! id.startsWith( '\0' )
		);
		if ( chunk.isEntry ) {
			kind = 'entry';
			entry = entryByName.get( chunk.name );
		} else if (
			firstModule &&
			( named = ( config.namedChunks || [] ).find( ( n ) =>
				n.test.test( firstModule )
			) )
		) {
			kind = 'named';
		} else if ( chunk.isDynamicEntry ) {
			kind = 'lazy';
		}
		info.set( chunk.fileName, {
			chunk,
			...rewritten[ index ],
			kind,
			entry,
			named,
		} );
	} );

	// Name the lazy files after the first key that points at them.
	const lazyFile = new Map();
	const lazyKey = new Map();
	const usedNames = new Set();
	for ( const item of info.values() ) {
		for ( const [ key, target ] of item.lazy ) {
			const targetInfo = info.get( target );
			if (
				! targetInfo ||
				targetInfo.kind === 'entry' ||
				targetInfo.kind === 'named' ||
				lazyFile.has( target )
			) {
				continue;
			}
			let name = config.lazyFile( key, state.lazyNames.get( key ) );
			while ( usedNames.has( name ) ) {
				name = name.replace( /\.js$/, '-2.js' );
			}
			usedNames.add( name );
			lazyFile.set( target, name );
			lazyKey.set( name, key );
		}
	}
	// Shared code that lazy files use. A big piece gets its own file, like
	// webpack's async split chunks; a small one is copied into each user.
	const splitFile = new Map();
	if ( config.splitCommon ) {
		const seen = new Set();
		const stack = [ ...lazyFile.keys() ];
		while ( stack.length ) {
			const current = stack.pop();
			const item = info.get( current );
			if ( ! item || seen.has( current ) ) {
				continue;
			}
			seen.add( current );
			stack.push( ...item.chunk.imports );
			// Webpack checks the size of each kind of code on its own, so
			// shared styles over the limit get their own file too.
			const big = async () =>
				item.code.length >= config.splitCommon.minBytes ||
				( item.css.length > 0 &&
					config.css?.bytes &&
					( await config.css.bytes( item.css ) ) >=
						config.splitCommon.minBytes );
			if (
				item.kind === 'common' &&
				! lazyFile.has( current ) &&
				( await big() )
			) {
				const modules = Object.keys( item.chunk.modules )
					.map( ( id ) => path.relative( config.root, id ) )
					.sort();
				splitFile.set(
					current,
					config.splitCommon.file( hash( modules.join( '\n' ), 8 ) )
				);
			}
		}
	}
	const outFile = ( item ) => {
		if ( item.kind === 'entry' ) {
			return item.entry.out;
		}
		if ( item.kind === 'named' ) {
			return item.named.file;
		}
		return (
			lazyFile.get( item.chunk.fileName ) ||
			splitFile.get( item.chunk.fileName ) ||
			null
		);
	};

	// What every entry that can load a lazy file already carries. The lazy
	// file leaves those chunks out, as webpack does.
	let parents = null;
	const heldByParents = ( roots ) => {
		if ( ! parents ) {
			parents = [ ...info.values() ]
				.filter( ( item ) => item.kind === 'entry' )
				.map( ( item ) => {
					const carried = new Set();
					const stack = [ item.chunk.fileName ];
					while ( stack.length ) {
						const current = stack.pop();
						if ( ! info.has( current ) || carried.has( current ) ) {
							continue;
						}
						carried.add( current );
						stack.push( ...info.get( current ).chunk.imports );
					}
					return { carried, reached: reach( item.chunk.fileName ) };
				} );
		}
		let held = null;
		for ( const parent of parents ) {
			if ( ! roots.some( ( root ) => parent.reached.has( root ) ) ) {
				continue;
			}
			held = held
				? new Set(
						[ ...held ].filter( ( f ) => parent.carried.has( f ) )
					)
				: new Set( parent.carried );
		}
		return held || new Set();
	};
	// The chunks a file must carry itself, and the named chunks it needs
	// loaded first. A named file carries only the chunks of its group: the
	// entries that use them bring everything else those chunks import.
	const closure = ( roots, kind ) => {
		if ( kind === 'named' ) {
			return { inline: roots, named: [] };
		}
		const inline = [];
		const named = [];
		const split = [];
		const seen = new Set();
		const held = kind === 'entry' ? null : heldByParents( roots );
		const visit = ( current, isRoot ) => {
			const item = info.get( current );
			if ( ! item || seen.has( current ) ) {
				return;
			}
			seen.add( current );
			if ( ! isRoot && held?.has( current ) && item.kind !== 'named' ) {
				return;
			}
			// A chunk that has its own file (a split file, or another lazy
			// file) is loaded, not copied, as webpack does.
			if (
				kind !== 'entry' &&
				! isRoot &&
				( splitFile.has( current ) || lazyFile.has( current ) )
			) {
				split.push( current );
				return;
			}
			for ( const imported of item.chunk.imports ) {
				visit( imported, false );
			}
			( item.kind === 'named' ? named : inline ).push( current );
		};
		roots.forEach( ( root ) => visit( root, true ) );
		return { inline, named, split };
	};
	// A lazy chunk plus every split file it needs, directly or not.
	const withSplits = ( file ) => {
		const all = [ file ];
		for ( const current of all ) {
			for ( const next of closure( [ current ] ).split ) {
				if ( ! all.includes( next ) ) {
					all.push( next );
				}
			}
		}
		return all;
	};
	// Every chunk an entry can reach, lazy ones included.
	const reach = ( file ) => {
		const seen = new Set();
		const stack = [ file ];
		while ( stack.length ) {
			const current = stack.pop();
			const item = info.get( current );
			if ( ! item || seen.has( current ) ) {
				continue;
			}
			seen.add( current );
			stack.push( ...item.chunk.imports, ...item.chunk.dynamicImports );
		}
		return seen;
	};

	// The output files and the chunks each one starts from.
	const outputs = new Map();
	for ( const [ file, item ] of info ) {
		const out = outFile( item );
		if ( ! out ) {
			continue;
		}
		if ( ! outputs.has( out ) ) {
			outputs.set( out, {
				out,
				kind: item.kind === 'common' ? 'lazy' : item.kind,
				roots: [],
				item,
			} );
		}
		outputs.get( out ).roots.push( file );
	}
	for ( const n of config.namedChunks || [] ) {
		// Webpack writes a named chunk even when nothing lands in it.
		if ( ! outputs.has( n.file ) ) {
			outputs.set( n.file, {
				out: n.file,
				kind: 'named',
				roots: [],
				item: null,
			} );
		}
	}
	const core =
		'var R=self.__NAME__||(self.__NAME__={d:{},c:{},w:[],s:{},z:{},' +
		'r:function(i){var m=R.c[i];if(!m){m=R.c[i]={exports:{}};R.d[i].call(m.exports,m,m.exports,function(p){return R.q(i,p)})}return m.exports},' +
		// A require() from chunk i: a global for an external, else another
		// chunk by its path relative to chunk i.
		'q:function(i,p){var o=window,s;if(p.indexOf("wc-g:")===0){s=p.slice(5).split(".");for(var k=0;k<s.length;k++)o=o[s[k]];return o}' +
		's=i.split("/");s.pop();p.split("/").forEach(function(x){x===".."?s.pop():x!=="."&&s.push(x)});return R.r(s.join("/"))},' +
		't:function(e){for(var k=0;k<e[0].length;k++)if(!R.d[e[0][k]])return 0;R.r(e[1]);return 1},' +
		'f:function(i,f){if(!R.d[i]){R.d[i]=f;var w=R.w;R.w=[];w.forEach(function(e){R.t(e)||R.w.push(e)})}},' +
		'e:function(d,i){var e=[d,i];R.t(e)||R.w.push(e)}});';
	const publicPath =
		config.publicPath === 'auto'
			? 'R.p=R.p||function(){var s=document.currentScript&&document.currentScript.src||"";return s.split("?")[0].slice(0,-__LEN__)}();'
			: `R.g=function(){return self.${ config.publicPath.global }||""};`;
	const loader =
		'R.L=R.L||function(k){return Promise.all((R.z[k]||[]).map(function(f){var i=f[0];' +
		'if(f[2]){if(R.s[i])return R.s[i];var l=document.createElement("link"),h=(R.g?R.g():R.p)+f[1];' +
		'if(document.dir==="rtl")h=h.replace(/\\.css(?:$|\\?)/,"-rtl$&");l.rel="stylesheet";l.href=h;' +
		'return R.s[i]=new Promise(function(a,b){l.onload=a;l.onerror=b;document.head.appendChild(l)})}' +
		'if(R.d[i])return;i=f[1];return R.s[i]||(R.s[i]=new Promise(function(a,b){var s=document.createElement("script");' +
		's.src=(R.g?R.g():R.p)+f[1];s.onload=a;s.onerror=b;document.head.appendChild(s)}))}))};' +
		'var __wcLazy=function(k,t){return R.L(k).then(t)};';

	const render = ( output ) => {
		const { out, roots, kind } = output;
		const { inline, named } = closure( roots, kind );
		// Only entries hold the lazy file map, for every chunk they can
		// reach, like webpack's runtime. A lazy file that listed its own
		// lazy files would need their hashes, and those can form a loop.
		const manifest = {};
		for ( const inlined of kind === 'entry' ? reach( roots[ 0 ] ) : [] ) {
			for ( const [ key, target ] of info.get( inlined ).lazy ) {
				const targetInfo = info.get( target );
				const targetOut = targetInfo && outFile( targetInfo );
				if ( ! targetOut || targetInfo.kind === 'entry' ) {
					continue;
				}
				const own =
					targetInfo.kind === 'named' ? [] : withSplits( target );
				const needed = new Set( [ target, ...own ] );
				for ( const f of own ) {
					closure( [ f ] ).named.forEach( ( n ) => needed.add( n ) );
				}
				manifest[ key ] = [ ...needed ].map( ( f ) => {
					const file = outFile( info.get( f ) );
					return [ f, `${ file }?ver=${ versionOf( file ) }` ];
				} );
				for ( const f of config.css ? own : [] ) {
					const cssFile = config.css.lazyFile(
						outFile( info.get( f ) ),
						closure( [ f ] ).inline.some(
							( c ) => info.get( c ).css.length > 0
						)
					);
					if ( cssFile ) {
						manifest[ key ].push( [ cssFile, cssFile, 1 ] );
					}
				}
			}
		}
		let code = `(function(){"use strict";${ core.replaceAll(
			'__NAME__',
			config.registry
		) }`;
		const hasLazy = Object.keys( manifest ).length > 0;
		if ( hasLazy || config.publicPath === 'auto' ) {
			code += publicPath.replace( '__LEN__', String( out.length ) );
			code += 'var __wcPublicPath=function(){return R.g?R.g():R.p};';
		}
		if ( hasLazy ) {
			code += `${ loader }Object.assign(R.z,${ JSON.stringify( manifest ) });`;
		} else if (
			inline.some( ( f ) => info.get( f ).code.includes( '__wcLazy(' ) )
		) {
			code +=
				kind === 'entry'
					? 'var __wcLazy=function(k,t){return Promise.resolve().then(t)};'
					: 'var __wcLazy=function(k,t){return R.L(k).then(t)};';
		}
		const pieces = [];
		for ( const inlined of inline ) {
			const item = info.get( inlined );
			code += `R.f(${ JSON.stringify(
				inlined
			) },function(module,exports,require){${
				item.exportHelper
					? 'var __wcX=function(n,g){Object.defineProperty(exports,n,{enumerable:!0,get:g})};'
					: ''
			}`;
			pieces.push( { start: code.length, file: inlined } );
			code += `${ item.code }\n});`;
		}
		renderedPieces.set( out, pieces );
		if ( kind === 'entry' ) {
			code += `R.e(${ JSON.stringify( named ) },${ JSON.stringify( roots[ 0 ] ) });`;
		}
		return code + '})();\n';
	};
	const rendered = new Map();
	const renderedPieces = new Map();
	// The output file's map: our wrapper code maps nowhere, each chunk's
	// code maps through Rolldown's map of that chunk to the sources.
	const mapOf = ( out ) => {
		const code = codeOf( out );
		const bundle = new Bundle( { separator: '' } );
		let position = 0;
		for ( const { start, file } of renderedPieces.get( out ) ) {
			bundle.addSource( {
				content: new MagicString( code.slice( position, start ) ),
			} );
			const item = info.get( file );
			bundle.addSource( { filename: file, content: item.ms } );
			position = start + item.code.length;
		}
		bundle.addSource( {
			content: new MagicString( code.slice( position ) ),
		} );
		const linked = bundle.generateMap( { hires: 'boundary' } );
		const outDir = path.resolve( config.outDir );
		const map = remapping( linked, ( file ) => {
			const chunk = info.get( file )?.chunk;
			if ( ! chunk?.map ) {
				return null;
			}
			// Rolldown's sources are relative to the chunk file.
			const chunkDir = path.dirname(
				path.join( outDir, chunk.fileName )
			);
			const json =
				typeof chunk.map.toString === 'function'
					? JSON.parse( chunk.map.toString() )
					: chunk.map;
			return {
				...json,
				sources: json.sources.map( ( source ) =>
					path.resolve( chunkDir, source )
				),
				sourcesContent: json.sourcesContent,
			};
		} );
		const fileDir = path.dirname( path.join( outDir, out ) );
		map.file = path.posix.basename( out );
		map.sources = map.sources.map( ( source ) =>
			source
				? path.relative( fileDir, source ).split( path.sep ).join( '/' )
				: source
		);
		return JSON.stringify( map );
	};
	const codeOf = ( out ) => {
		if ( ! rendered.has( out ) ) {
			rendered.set( out, render( outputs.get( out ) ) );
		}
		return rendered.get( out );
	};
	const versionOf = ( out ) => hash( codeOf( out ) );

	// A split file is only needed when an entry's lazy file map lists it.
	// When every entry that reaches it already carries its code, nothing
	// loads it, so it is not written.
	const splitOuts = new Set( splitFile.values() );
	const entryCode = [ ...outputs.values() ]
		.filter( ( output ) => output.kind === 'entry' )
		.map( ( output ) => codeOf( output.out ) );
	for ( const out of splitOuts ) {
		if ( ! entryCode.some( ( code ) => code.includes( `${ out }?ver=` ) ) ) {
			outputs.delete( out );
		}
	}

	state.outputs = [];
	const assetCheck = {};
	const cssByOutput = new Map();
	for ( const output of outputs.values() ) {
		const { out, kind, roots, item } = output;
		const code = codeOf( out );
		cssByOutput.set( out, { roots, kind, item } );
		state.outputs.push( { out, kind, bytes: code.length } );
		if ( config.cssOnly ) {
			continue;
		}
		if ( config.sourcemap ) {
			emit( `${ out }.map`, mapOf( out ) );
			emit(
				out,
				`${ code }//# sourceMappingURL=${ path.posix.basename( out ) }.map\n`
			);
		} else {
			const legal = new Set(
				renderedPieces
					.get( out )
					.flatMap( ( { file } ) => info.get( file ).legal )
			);
			if ( legal.size ) {
				const name = `${ path.posix.basename( out ) }.LICENSE.txt`;
				emit(
					`${ out }.LICENSE.txt`,
					`${ [ ...legal ].sort().join( '\n\n' ) }\n`
				);
				emit(
					out,
					`/*! For license information please see ${ name } */\n${ code }`
				);
			} else {
				emit( out, code );
			}
		}

		if ( kind === 'lazy' ) {
			continue;
		}
		let dependencies = config.injectPolyfill ? [ 'wp-polyfill' ] : [];
		if ( kind === 'entry' ) {
			const handles = new Set( dependencies );
			for ( const file of reach( roots[ 0 ] ) ) {
				for ( const imported of info.get( file ).chunk.imports ) {
					if ( imported.startsWith( EXTERNAL ) ) {
						info.get( file ).externals.add( imported );
					}
				}
				for ( const id of info.get( file ).externals ) {
					for ( const request of state.externalRequests.get( id ) ||
						[] ) {
						handles.add( config.toHandle( request ) );
					}
				}
			}
			dependencies = [ ...handles ].sort();
			if ( config.addSplitDeps ) {
				dependencies = [
					...config.namedChunks.map( ( n ) => n.name ),
					...dependencies,
				];
			}
		}
		if ( config.assetFiles !== false && ASSET_CHECK ) {
			assetCheck[ out ] = { dependencies, version: hash( code ) };
		} else if ( config.assetFiles !== false ) {
			emit(
				out.replace( /\.js$/, '.asset.php' ),
				assetPhp( { dependencies, version: hash( code ) } )
			);
		}
	}

	if ( config.css ) {
		await config.css.plan( {
			ctx,
			info,
			outputs,
			closure,
			reach,
			outFile,
			emit,
			state,
			// The module each output starts from, for ordering styles.
			rootModules: ( output ) => {
				if ( output.kind === 'entry' ) {
					return [ output.item.chunk.facadeModuleId ];
				}
				return lazyKey.has( output.out )
					? [ state.lazyModules.get( lazyKey.get( output.out ) ) ]
					: Object.keys( output.item.chunk.modules );
			},
		} );
	}
	if ( ANALYZE ) {
		// What each written script holds, for the bundle report.
		for ( const [ out, pieces ] of renderedPieces ) {
			const modules = {};
			for ( const { file } of pieces ) {
				for ( const [ id, m ] of Object.entries(
					info.get( file ).chunk.modules
				) ) {
					modules[ path.relative( config.root, id ) ] =
						m.renderedLength;
				}
			}
			analysis.push( {
				build: config.name,
				out,
				bytes: files.get( out )?.length ?? 0,
				modules,
			} );
		}
	}
	if ( process.env.WC_DUMP ) {
		fs.writeFileSync(
			process.env.WC_DUMP,
			JSON.stringify(
				[ ...info ].map( ( [ file, item ] ) => ( {
					file,
					kind: item.kind,
					out: outFile( item ),
					bytes: item.code.length,
					imports: item.chunk.imports,
					modules: Object.fromEntries(
						Object.entries( item.chunk.modules ).map(
							( [ id, m ] ) => [
								path.relative( config.root, id ),
								m.renderedLength,
							]
						)
					),
				} ) )
			)
		);
	}
	if ( config.afterLink ) {
		config.afterLink( { emit, info, files } );
	}
	for ( const [ file, content ] of files ) {
		const target = path.join( config.outDir, file );
		fs.mkdirSync( path.dirname( target ), { recursive: true } );
		fs.writeFileSync( target, content );
	}
	if ( Object.keys( assetCheck ).length ) {
		// Every config adds its scripts to the one assets.json file.
		const target = path.join( config.outDir, 'assets.json' );
		const existing = fs.existsSync( target )
			? JSON.parse( fs.readFileSync( target, 'utf8' ) )
			: {};
		fs.mkdirSync( config.outDir, { recursive: true } );
		fs.writeFileSync(
			target,
			JSON.stringify( { ...existing, ...assetCheck } )
		);
	}
	state.files = [ ...files.keys() ];
}

export function rolldownOptions( config, state ) {
	const production = config.mode !== 'development';
	// Like webpack's `devtool: 'source-map'` in development builds.
	config.sourcemap = config.sourcemap ?? ! production;
	const input = {
		cwd: config.cwd,
		input: Object.fromEntries(
			config.entries.map( ( e ) => [ e.name, `wc-entry:${ e.name }` ] )
		),
		platform: 'browser',
		// tsconfig paths point some imports at .d.ts files; webpack ignores them too.
		tsconfig: false,
		// Some index files re-export TypeScript types without `type`. Webpack
		// turns those into undefined exports; do the same.
		shimMissingExports: true,
		preserveEntrySignatures: false,
		plugins: [
			...( config.plugins || [] ),
			production && ! config.cssOnly && propTypesPlugin(),
			classicPlugin( config, state ),
		].filter( Boolean ),
		resolve: {
			alias: config.alias,
			conditionNames: [
				// No 'import' here: Rolldown adds 'import' or 'require' per
				// statement. Forcing it hands ES modules to require() calls.
				'wc-source',
				'browser',
				'module',
				production ? 'production' : 'development',
				'default',
			],
			extensions: config.extensions,
			...( config.resolve || {} ),
		},
		moduleTypes: { '.js': 'jsx', ...( config.moduleTypes || {} ) },
		transform: {
			target: config.target,
			jsx: { runtime: 'automatic', development: false },
			define: {
				'process.env.NODE_ENV': JSON.stringify(
					production ? 'production' : 'development'
				),
				__i18n_text_domain__: '"woocommerce"',
				// Webpack maps Node's `global` to the global object for web builds.
				global: 'globalThis',
				...( config.define || {} ),
			},
		},
		logLevel: 'warn',
		checks: { pluginTimings: false },
		...( process.env.WC_INCREMENTAL
			? { experimental: { incrementalBuild: true } }
			: {} ),
		onLog( level, log, handler ) {
			if (
				[
					'EVAL',
					'MODULE_LEVEL_DIRECTIVE',
					'SHIMMED_EXPORT',
					'IMPORT_IS_UNDEFINED',
				].includes( log.code )
			) {
				state.logCounts[ log.code ] =
					( state.logCounts[ log.code ] || 0 ) + 1;
				return;
			}
			handler( level, log );
		},
	};
	const output = {
		dir: config.outDir,
		format: 'cjs',
		minify: config.minify ?? production,
		sourcemap: Boolean( config.sourcemap ),
		comments: {
			legal: Boolean( config.licenses && production ),
			annotation: false,
			jsdoc: false,
		},
		entryFileNames: '[name].js',
		chunkFileNames: '_/[name]-[hash].js',
		strictExecutionOrder: config.strict !== false,
		// eslint-disable-next-line no-nested-ternary
		codeSplitting: config.single
			? false
			: config.namedChunks?.length
				? {
						groups: config.namedChunks.map( ( n ) => ( {
							name: n.name,
							test: n.test,
							// One chunk per set of entries, so a chunk only holds what
							// its entries use. The linker puts them all in one file.
							entriesAware: true,
							entriesAwareMergeThreshold: 0,
							includeDependenciesRecursively: false,
							...( n.group || {} ),
						} ) ),
					}
				: undefined,
	};
	return { input, output };
}

export const newState = () => ( {
	lazyNames: new Map(),
	lazyModules: new Map(),
	lazyKeysByModule: new Map(),
	externalRequests: new Map(),
	logCounts: {},
} );

export async function buildClassic( config ) {
	const state = newState();
	const started = performance.now();
	const { input, output } = rolldownOptions( config, state );
	const bundle = await rolldown( input );
	await bundle.generate( output );
	await bundle.close();
	return {
		name: config.name,
		ms: Math.round( performance.now() - started ),
		linkMs: state.linkMs,
		files: state.files.length,
		logs: state.logCounts,
		state,
	};
}

export function watchClassic( config, onEvent ) {
	const state = newState();
	const { input, output } = rolldownOptions( config, state );
	const watcher = watch( { ...input, output } );
	watcher.on( 'event', ( event ) => onEvent( event, state ) );
	return watcher;
}

/** Watch a config and print one line per rebuild or error. */
export function watchAndLog( config ) {
	watchClassic( config, ( event ) => {
		if ( event.code === 'BUNDLE_END' ) {
			console.log( `Built ${ config.name } in ${ event.duration } ms` );
			event.result?.close();
		} else if ( event.code === 'ERROR' ) {
			console.error( `Error in ${ config.name }: ${ event.error }` );
		}
	} );
	return { name: config.name };
}
