import path from 'node:path';
import { BLOCKS } from '@woocommerce/rolldown-build/shared';
import {
	createCss,
	cssMarkPlugin,
	orderedMarks,
} from '@woocommerce/rolldown-build/css';
import { buildClassic } from '@woocommerce/rolldown-build/classic';
import { OUT, base, getEntryConfig, blocksRequire } from './config.mjs';

const { styleEntries, editorStyleEntries } = blocksRequire(
	'./bin/interactivity-entries.js'
);

// The `editorStyle` cache group: editor.scss files and editor-components.
const isEditorStyle = ( file ) =>
	file.endsWith( 'editor.scss' ) ||
	file.includes(
		`${ path.sep }assets${ path.sep }js${ path.sep }editor-components${ path.sep }`
	);

const unique = ( list ) => [ ...new Set( list ) ];

export async function buildBlocksStyles( {
	outDir = OUT,
	production = true,
	minifier = 'cssnano',
	sass = 'sass',
	css = createCss( 'blocks', { production, minifier, sass } ),
	// Watch mode passes a function that starts a watcher instead.
	run = buildClassic,
} = {} ) {
	const started = performance.now();
	const shared = {
		...base,
		outDir,
		cssOnly: true,
		minify: false,
		moduleTypes: {},
		extensions: [ '.js', '.jsx', '.ts', '.tsx' ],
		plugins: [ cssMarkPlugin( css ) ],
		lazyFile: ( key ) => `${ key }.js`,
	};
	const writeWithRtl = ( emit, file, content ) => {
		emit( file, content );
		emit( file.replace( /\.css$/, '-rtl.css' ), css.rtl( content ) );
	};

	const styling = {
		...shared,
		name: 'styling',
		registry: '__wcBlocksStyling',
		entries: Object.entries( getEntryConfig( 'styling' ) ).map(
			( [ name, files ] ) => ( {
				name,
				files: [].concat( files ),
				out: `${ name }-style.js`,
				library: null,
			} )
		),
		css: {
			lazyFile: () => null,
			async plan( { ctx, info, outputs, closure, emit, rootModules } ) {
				const marks = new Map();
				const parents = new Map();
				for ( const output of outputs.values() ) {
					const { inline } = closure( output.roots, output.kind );
					marks.set(
						output.out,
						orderedMarks(
							ctx,
							css,
							rootModules( output ),
							new Set(
								inline.flatMap( ( f ) => info.get( f ).css )
							)
						)
					);
					for ( const file of inline ) {
						for ( const target of info.get( file ).lazy.values() ) {
							for ( const other of outputs.values() ) {
								if (
									other.kind === 'lazy' &&
									other.roots.includes( target )
								) {
									parents.set( other.out, [
										...( parents.get( other.out ) || [] ),
										output.out,
									] );
								}
							}
						}
					}
				}
				// A lazy chunk leaves out what every parent already loaded.
				const available = new Map();
				const availableOf = ( out, trail = new Set() ) => {
					if ( available.has( out ) ) {
						return available.get( out );
					}
					const list = ( parents.get( out ) || [] ).filter(
						( p ) => ! trail.has( p )
					);
					let result = null;
					for ( const parent of list ) {
						const set = new Set( [
							...marks.get( parent ),
							...availableOf(
								parent,
								new Set( [ ...trail, out ] )
							),
						] );
						result = result
							? new Set(
									[ ...result ].filter( ( m ) =>
										set.has( m )
									)
								)
							: set;
					}
					available.set( out, result || new Set() );
					return available.get( out );
				};
				const editor = [];
				for ( const output of outputs.values() ) {
					const skip =
						output.kind === 'lazy'
							? availableOf( output.out )
							: new Set();
					const own = [];
					for ( const mark of marks.get( output.out ) ) {
						if ( skip.has( mark ) ) {
							continue;
						}
						( isEditorStyle( css.fileOf( mark ) )
							? editor
							: own
						).push( mark );
					}
					const content = await css.join( own );
					if ( content ) {
						const name =
							output.kind === 'entry'
								? output.item.entry.name
								: output.out.replace( /\.js$/, '' );
						writeWithRtl( emit, `${ name }.css`, content );
					}
				}
				const editorContent = await css.join( unique( editor ) );
				if ( editorContent ) {
					writeWithRtl(
						emit,
						'wc-blocks-editor-style.css',
						editorContent
					);
				}
			},
		},
	};

	const unified = {
		...shared,
		name: 'unified-styling',
		registry: '__wcBlocksUnifiedStyling',
		single: true,
		entries: [
			{
				name: 'wc-block-library-style-source',
				files: [
					...Object.values( getEntryConfig( 'styling' ) ).flat(),
					...Object.values( styleEntries ).flat(),
					...Object.values( editorStyleEntries ).flat(),
				],
				out: 'wc-block-library-style-source.js',
				library: null,
			},
		],
		css: {
			lazyFile: () => null,
			async plan( { ctx, info, outputs, emit, rootModules } ) {
				const all = orderedMarks(
					ctx,
					css,
					rootModules( [ ...outputs.values() ][ 0 ] ),
					new Set(
						[ ...info.values() ].flatMap( ( item ) => item.css )
					),
					{ dynamic: true }
				);
				let content = await css.join( all );
				let rtl = css.rtl( content );
				if ( production ) {
					// Minify the joined file once more to drop rules that
					// several entries repeat.
					[ content, rtl ] = await Promise.all( [
						css.minifyWhole( content ),
						css.minifyWhole( rtl ),
					] );
				}
				emit( 'wc-block-library-style.css', content );
				emit( 'wc-block-library-style-rtl.css', rtl );
			},
		},
	};

	// Interactivity block styles: one stylesheet per style.scss or editor.scss.
	const direct = async () => {
		const fs = await import( 'node:fs' );
		const entries = Object.entries( {
			...styleEntries,
			...editorStyleEntries,
		} );
		await Promise.all(
			entries.map( async ( [ name, file ] ) => {
				const content = await css.join( [
					css.mark( path.resolve( BLOCKS, file ) ),
				] );
				const target = path.join( outDir, `${ name }.css` );
				fs.mkdirSync( path.dirname( target ), { recursive: true } );
				fs.writeFileSync( target, content );
				fs.writeFileSync(
					target.replace( /\.css$/, '-rtl.css' ),
					css.rtl( content )
				);
			} )
		);
		return { name: 'interactivity-styles', files: entries.length * 2 };
	};

	const results = await Promise.all( [
		run( styling ),
		run( unified ),
		direct(),
	] );
	return {
		ms: Math.round( performance.now() - started ),
		css: {
			modules: css.stats.modules,
			sassMs: Math.round( css.stats.sassMs ),
			postcssMs: Math.round( css.stats.postcssMs ),
		},
		results: results.map( ( { state, ...rest } ) => rest ),
	};
}
