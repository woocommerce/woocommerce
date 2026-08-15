/**
 * Runs inside the page/iframe. Must stay self-contained (it is serialized
 * by Playwright's evaluate), so no imports and no outer-scope references.
 *
 * Collects:
 * - blocks: one entry per `wp-block-*` element, boxes relative to the root.
 * - visualBoxes: elements that paint a border or background. These are the
 *   primary parity signal — they are independent of the div-vs-table markup.
 * - overflow: horizontal overflow measurements.
 */
export function extractGeometryInPage( { rootSelector, overflowSelectors } ) {
	const root = document.querySelector( rootSelector );
	if ( ! root ) {
		return { error: `root not found: ${ rootSelector }` };
	}
	const rootRect = root.getBoundingClientRect();
	const tableInternals = [ 'TBODY', 'TR', 'TD', 'TH' ];
	const isBlockClass = ( c ) =>
		c.startsWith( 'wp-block-' ) &&
		! c.includes( '__' ) &&
		! c.includes( '-is-layout-' ) &&
		c !== 'wp-block-post-content';

	const relRect = ( r ) => ( {
		left: +( r.left - rootRect.left ).toFixed( 1 ),
		right: +( rootRect.right - r.right ).toFixed( 1 ),
		top: +( r.top - rootRect.top ).toFixed( 1 ),
		width: +r.width.toFixed( 1 ),
		height: +r.height.toFixed( 1 ),
	} );

	const blocks = [];
	for ( const el of root.querySelectorAll( '*' ) ) {
		const classes = Array.from( el.classList ).filter( isBlockClass );
		if ( ! classes.length ) {
			continue;
		}
		const type = classes[ 0 ];
		// Email markup wraps blocks in tables and sometimes repeats the block
		// class on an inner element (cell, or the h2 inside a heading table).
		// Count the block once: skip the inner copy when the path up to the
		// same-class table crosses only table internals.
		if ( el.tagName !== 'TABLE' ) {
			let ancestor = el.parentElement;
			let onlyTableInternals = true;
			while ( ancestor && ancestor !== root ) {
				if (
					ancestor.tagName === 'TABLE' &&
					ancestor.classList.contains( type )
				) {
					break;
				}
				if ( ! tableInternals.includes( ancestor.tagName ) ) {
					onlyTableInternals = false;
					break;
				}
				ancestor = ancestor.parentElement;
			}
			if (
				onlyTableInternals &&
				ancestor &&
				ancestor !== root &&
				ancestor.tagName === 'TABLE'
			) {
				continue;
			}
		}
		const r = el.getBoundingClientRect();
		if ( r.width === 0 && r.height === 0 ) {
			continue;
		}
		blocks.push( { type, tag: el.tagName.toLowerCase(), ...relRect( r ) } );
	}

	// Editor chrome that can paint boxes or images without being content.
	const chromeSelector =
		'.components-drop-zone, .block-list-appender, .components-resizable-box__handle, .block-editor-block-list__insertion-point';

	const visualBoxes = [];
	const seen = new Set();
	for ( const el of root.querySelectorAll( '*' ) ) {
		const tag = el.tagName.toLowerCase();
		const isImage =
			tag === 'img' || ( tag === 'svg' && ! el.ownerSVGElement );
		const cs = getComputedStyle( el );
		if (
			cs.display === 'none' ||
			cs.visibility === 'hidden' ||
			parseFloat( cs.opacity ) === 0 ||
			el.closest( chromeSelector )
		) {
			continue;
		}
		const side = ( styleProp, widthProp ) =>
			cs[ styleProp ] === 'none' || cs[ styleProp ] === 'hidden'
				? 0
				: +( parseFloat( cs[ widthProp ] ) || 0 ).toFixed( 1 );
		const borders = [
			side( 'borderTopStyle', 'borderTopWidth' ),
			side( 'borderRightStyle', 'borderRightWidth' ),
			side( 'borderBottomStyle', 'borderBottomWidth' ),
			side( 'borderLeftStyle', 'borderLeftWidth' ),
		];
		const bg = cs.backgroundColor;
		const hasBg = bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent';
		if ( ! isImage && ! borders.some( ( w ) => w > 0 ) && ! hasBg ) {
			continue;
		}
		const r = el.getBoundingClientRect();
		if ( r.width === 0 || r.height === 0 ) {
			continue;
		}
		// Text alignment is only meaningful on elements that hold text
		// themselves (headings, cells, paragraphs) — not on containers.
		// The editor's contenteditable RichText wrappers ("rich-text" divs)
		// are transparent: text inside them still belongs to this element.
		const blockChildren = el.querySelectorAll(
			'div, p, h1, h2, h3, h4, h5, h6, table, ul, ol, figure, blockquote'
		);
		const holdsText =
			el.textContent.trim() &&
			Array.from( blockChildren ).every( ( b ) =>
				b.classList.contains( 'rich-text' )
			);
		const alignMap = {
			start: 'left',
			end: 'right',
			'-webkit-center': 'center',
		};
		const align = holdsText
			? alignMap[ cs.textAlign ] ?? cs.textAlign
			: null;
		const kind = isImage ? 'image' : 'painted';
		const rect = relRect( r );
		const key = JSON.stringify( [
			kind,
			align,
			Math.round( rect.left ),
			Math.round( rect.top ),
			Math.round( rect.width ),
			Math.round( rect.height ),
			borders,
			hasBg ? bg : 'none',
		] );
		// Editor and email markup both sometimes paint the same box twice
		// (nested wrappers with identical bounds) — count it once.
		if ( seen.has( key ) ) {
			continue;
		}
		seen.add( key );
		visualBoxes.push( {
			tag,
			kind,
			align,
			...rect,
			borders,
			background: hasBg ? bg : 'none',
		} );
	}

	const overflow = {};
	for ( const sel of [ ':document', ...( overflowSelectors ?? [] ) ] ) {
		const target =
			sel === ':document'
				? document.documentElement
				: document.querySelector( sel );
		if ( ! target ) {
			continue;
		}
		overflow[ sel ] = {
			scrollWidth: target.scrollWidth,
			clientWidth: target.clientWidth,
			overflowPx: Math.max( 0, target.scrollWidth - target.clientWidth ),
		};
	}

	return {
		rootWidth: +rootRect.width.toFixed( 1 ),
		rootHeight: +rootRect.height.toFixed( 1 ),
		blocks,
		visualBoxes,
		overflow,
	};
}

/**
 * Aligns the two box sequences in document order, allowing skips on either
 * side, so one missing box does not desynchronize every later pair. A pair
 * with a total position/size difference larger than roughly two skip
 * penalties splits into "missing" + "extra" instead of matching.
 */
function alignBoxes( editorBoxes, emailBoxes ) {
	const SKIP = 150;
	const cost = ( a, b ) =>
		Math.abs( a.left - b.left ) +
		Math.abs( a.top - b.top ) +
		Math.abs( a.width - b.width ) +
		Math.abs( a.height - b.height ) +
		( a.background !== b.background ? 100 : 0 ) +
		( ( a.kind ?? 'painted' ) !== ( b.kind ?? 'painted' ) ? 150 : 0 );

	const n = editorBoxes.length;
	const m = emailBoxes.length;
	const dp = Array.from( { length: n + 1 }, () =>
		new Array( m + 1 ).fill( 0 )
	);
	for ( let i = 1; i <= n; i++ ) {
		dp[ i ][ 0 ] = i * SKIP;
	}
	for ( let j = 1; j <= m; j++ ) {
		dp[ 0 ][ j ] = j * SKIP;
	}
	for ( let i = 1; i <= n; i++ ) {
		for ( let j = 1; j <= m; j++ ) {
			dp[ i ][ j ] = Math.min(
				dp[ i - 1 ][ j - 1 ] +
					cost( editorBoxes[ i - 1 ], emailBoxes[ j - 1 ] ),
				dp[ i - 1 ][ j ] + SKIP,
				dp[ i ][ j - 1 ] + SKIP
			);
		}
	}

	const matched = [];
	const matchedEditor = new Set();
	const matchedEmail = new Set();
	let i = n;
	let j = m;
	while ( i > 0 && j > 0 ) {
		if (
			dp[ i ][ j ] ===
			dp[ i - 1 ][ j - 1 ] +
				cost( editorBoxes[ i - 1 ], emailBoxes[ j - 1 ] )
		) {
			matched.unshift( [ editorBoxes[ i - 1 ], emailBoxes[ j - 1 ] ] );
			matchedEditor.add( i - 1 );
			matchedEmail.add( j - 1 );
			i--;
			j--;
		} else if ( dp[ i ][ j ] === dp[ i - 1 ][ j ] + SKIP ) {
			i--;
		} else {
			j--;
		}
	}

	return {
		matched,
		unmatchedEditor: editorBoxes.filter(
			( _, k ) => ! matchedEditor.has( k )
		),
		unmatchedEmail: emailBoxes.filter(
			( _, k ) => ! matchedEmail.has( k )
		),
	};
}

/**
 * Compares extracted geometry from both surfaces. Runs in Node.
 *
 * Visual boxes (borders/backgrounds) are the pass/fail signal. Block boxes
 * are reported as informational context: the email side measures wrapper
 * tables, whose bounds legitimately differ from editor divs (root padding
 * is distributed into the blocks during email rendering).
 */
export function compareGeometry( editorGeo, emailGeo, tolerances ) {
	const rows = [];
	const warnings = [];
	const global = [];
	const pairs = [];

	const boxMetrics = [
		[ 'left', tolerances.horizontal ],
		[ 'right', tolerances.horizontal ],
		[ 'width', tolerances.horizontal ],
		[ 'top', tolerances.vertical ],
		[ 'height', tolerances.vertical ],
	];

	// The email content area growing wider than the editor canvas is the
	// classic "email expands because of overflowing content" bug.
	{
		const delta = +( emailGeo.rootWidth - editorGeo.rootWidth ).toFixed(
			1
		);
		const row = {
			block: 'root',
			metric: 'content width',
			editor: editorGeo.rootWidth,
			email: emailGeo.rootWidth,
			delta,
			ok: Math.abs( delta ) <= tolerances.horizontal,
		};
		rows.push( row );
		if ( ! row.ok ) {
			global.push( row );
		}
	}
	for ( const [ surface, geo ] of [
		[ 'editor', editorGeo ],
		[ 'email', emailGeo ],
	] ) {
		for ( const [ sel, o ] of Object.entries( geo.overflow ?? {} ) ) {
			if ( o.overflowPx > 0 ) {
				const row = {
					block: `${ surface } ${ sel }`,
					metric: 'horizontal overflow',
					surface,
					editor: surface === 'editor' ? `+${ o.overflowPx }px` : '—',
					email: surface === 'email' ? `+${ o.overflowPx }px` : '—',
					delta: o.overflowPx,
					ok: false,
				};
				rows.push( row );
				global.push( row );
			}
		}
	}

	const editorBoxes = editorGeo.visualBoxes ?? [];
	const emailBoxes = emailGeo.visualBoxes ?? [];
	if ( editorBoxes.length !== emailBoxes.length ) {
		warnings.push(
			`Visual box count mismatch: editor paints ${ editorBoxes.length } bordered/background boxes, email paints ${ emailBoxes.length }.`
		);
	}
	const { matched, unmatchedEditor, unmatchedEmail } = alignBoxes(
		editorBoxes,
		emailBoxes
	);
	let prevTopDelta = 0;
	for ( let i = 0; i < matched.length; i++ ) {
		const [ ed, em ] = matched[ i ];
		const label = `visual[${ i }] (${ ed.tag }→${ em.tag })`;
		const issues = [];
		for ( const [ metric, tol ] of boxMetrics ) {
			const delta = +( em[ metric ] - ed[ metric ] ).toFixed( 1 );
			// Vertical drift accumulates down the page: one wrong margin
			// shifts every later box. Blame only the box where the drift
			// changes, not everything below it.
			const localDelta =
				metric === 'top'
					? +( delta - prevTopDelta ).toFixed( 1 )
					: delta;
			const row = {
				block: label,
				metric,
				editor: ed[ metric ],
				email: em[ metric ],
				delta,
				localDelta,
				ok: Math.abs( localDelta ) <= tol,
			};
			rows.push( row );
			if ( ! row.ok ) {
				issues.push( row );
			}
			if ( metric === 'top' ) {
				prevTopDelta = delta;
			}
		}
		const sides = [ 'top', 'right', 'bottom', 'left' ];
		for ( let s = 0; s < 4; s++ ) {
			const delta = +( em.borders[ s ] - ed.borders[ s ] ).toFixed( 1 );
			if ( ed.borders[ s ] === 0 && em.borders[ s ] === 0 ) {
				continue;
			}
			const row = {
				block: label,
				metric: `border-${ sides[ s ] }`,
				editor: ed.borders[ s ],
				email: em.borders[ s ],
				delta,
				ok: Math.abs( delta ) <= 0.5,
			};
			rows.push( row );
			if ( ! row.ok ) {
				issues.push( row );
			}
		}
		if ( ed.background !== em.background ) {
			const row = {
				block: label,
				metric: 'background',
				editor: ed.background,
				email: em.background,
				delta: null,
				ok: null,
			};
			rows.push( row );
			issues.push( row );
		}
		if ( ed.align && em.align && ed.align !== em.align ) {
			const row = {
				block: label,
				metric: 'text-align',
				editor: ed.align,
				email: em.align,
				delta: null,
				ok: false,
			};
			rows.push( row );
			issues.push( row );
		}
		pairs.push( { index: i, editor: ed, email: em, issues } );
	}

	// Row-level alignment: boxes sharing a horizontal band in the editor are
	// treated as one row, and the row's position within the root is
	// classified as left/center/right. A classification change ("centered in
	// the editor, left-aligned in the email") is a failure — raw left deltas
	// alone don't say this clearly. Near-full-width rows carry no alignment
	// information and are skipped.
	const bands = [];
	const absorbedEditor = new Set();
	const absorbedEmail = new Set();
	{
		const classify = ( left, width, rootW ) => {
			const d = +( left - ( rootW - left - width ) ).toFixed( 1 );
			return {
				d,
				cls: Math.abs( d ) <= 8 ? 'center' : d < 0 ? 'left' : 'right',
			};
		};
		const bbox = ( boxes ) => {
			const left = Math.min( ...boxes.map( ( b ) => b.left ) );
			const top = Math.min( ...boxes.map( ( b ) => b.top ) );
			const right = Math.max( ...boxes.map( ( b ) => b.left + b.width ) );
			const bottom = Math.max(
				...boxes.map( ( b ) => b.top + b.height )
			);
			return { left, top, width: right - left, height: bottom - top };
		};
		const contains = ( a, b ) =>
			b !== a &&
			b.left >= a.left - 1 &&
			b.top >= a.top - 1 &&
			b.left + b.width <= a.left + a.width + 1 &&
			b.top + b.height <= a.top + a.height + 1;

		// Unmatched boxes must take part here: when a row's justification is
		// completely lost, the boxes move so far that the matcher gives up on
		// them — exactly the case an alignment finding is for.
		const entries = [
			...matched.map( ( m, i ) => ( {
				ed: m[ 0 ],
				em: m[ 1 ],
				pairIndex: i,
			} ) ),
			...unmatchedEditor.map( ( box ) => ( {
				ed: box,
				em: null,
				pairIndex: null,
			} ) ),
		]
			.filter( ( e ) => e.ed.width < editorGeo.rootWidth * 0.85 )
			.sort( ( a, b ) => a.ed.top - b.ed.top );

		let current = [];
		const flush = () => {
			if ( ! current.length ) {
				return;
			}
			const band = current;
			current = [];
			const edBoxes = band.map( ( e ) => e.ed );
			const edBox = bbox( edBoxes );
			if ( edBox.width >= editorGeo.rootWidth * 0.85 ) {
				return;
			}
			// Email side: matched counterparts plus unmatched email boxes at
			// the same vertical position (the matcher may have skipped them).
			const matchedEm = band.filter( ( e ) => e.em ).map( ( e ) => e.em );
			const refTop = matchedEm.length
				? Math.min( ...matchedEm.map( ( b ) => b.top ) )
				: edBox.top;
			const extraEm = unmatchedEmail.filter(
				( b ) =>
					b.width < emailGeo.rootWidth * 0.85 &&
					Math.abs( b.top - refTop ) <= 24
			);
			const emBoxes = [ ...matchedEm, ...extraEm ];
			if ( ! emBoxes.length ) {
				return;
			}
			const emBox = bbox( emBoxes );
			const edAlign = classify(
				edBox.left,
				edBox.width,
				editorGeo.rootWidth
			);
			const emAlign = classify(
				emBox.left,
				emBox.width,
				emailGeo.rootWidth
			);
			if (
				edAlign.cls === emAlign.cls ||
				Math.abs( edAlign.d - emAlign.d ) <= 16
			) {
				return;
			}
			rows.push( {
				block: `row at ${ Math.round( edBox.top ) }px`,
				metric: 'alignment',
				editor: edAlign.cls,
				email: emAlign.cls,
				delta: null,
				ok: false,
			} );
			// Count visible elements, not nested wrappers (an icon's svg sits
			// inside its background box).
			const topLevel = edBoxes.filter(
				( b ) => ! edBoxes.some( ( o ) => contains( o, b ) )
			);
			bands.push( {
				count: topLevel.length,
				editorBox: edBox,
				emailBox: emBox,
				editorAlign: edAlign.cls,
				emailAlign: emAlign.cls,
			} );
			// The band explains the members' horizontal deltas — drop their
			// left/right issues so each icon is not reported again.
			for ( const e of band ) {
				if ( e.pairIndex !== null ) {
					const p = pairs[ e.pairIndex ];
					p.issues = p.issues.filter(
						( i ) => i.metric !== 'left' && i.metric !== 'right'
					);
				} else {
					// An unmatched editor member with a same-size unmatched
					// email box is a moved box, not a lost one.
					const twin = extraEm.find(
						( b ) =>
							! absorbedEmail.has( b ) &&
							Math.abs( b.width - e.ed.width ) <= 20 &&
							Math.abs( b.height - e.ed.height ) <= 20
					);
					if ( twin ) {
						absorbedEditor.add( e.ed );
						absorbedEmail.add( twin );
					}
				}
			}
		};
		for ( const e of entries ) {
			if (
				current.length &&
				Math.abs( e.ed.top - current[ 0 ].ed.top ) > 12
			) {
				flush();
			}
			current.push( e );
		}
		flush();
	}
	// Editor boxes with no email counterpart: the element loses its
	// background/border entirely in the email.
	for ( const box of unmatchedEditor ) {
		if ( absorbedEditor.has( box ) ) {
			continue;
		}
		const row = {
			block: `visual (unmatched)`,
			metric: 'missing',
			editor: 'painted',
			email: 'not painted',
			delta: null,
			ok: false,
		};
		rows.push( row );
		pairs.push( {
			index: pairs.length,
			editor: box,
			email: null,
			issues: [ row ],
		} );
	}
	// Extra email-side boxes are usually harmless wrappers — report as info.
	const extraEmail = unmatchedEmail.filter(
		( b ) => ! absorbedEmail.has( b )
	);
	if ( extraEmail.length ) {
		warnings.push(
			`The email paints ${ extraEmail.length } extra bordered/background box(es) with no editor counterpart — usually harmless table wrappers.`
		);
	}

	// Informational block-box comparison (class-based mapping).
	const groupByType = ( geo ) => {
		const map = new Map();
		for ( const b of geo.blocks ) {
			if ( ! map.has( b.type ) ) {
				map.set( b.type, [] );
			}
			map.get( b.type ).push( b );
		}
		return map;
	};
	const editorByType = groupByType( editorGeo );
	const emailByType = groupByType( emailGeo );
	for ( const type of new Set( [
		...editorByType.keys(),
		...emailByType.keys(),
	] ) ) {
		const edBlocks = editorByType.get( type ) ?? [];
		const emBlocks = emailByType.get( type ) ?? [];
		if ( edBlocks.length !== emBlocks.length ) {
			warnings.push(
				`Block count mismatch for ${ type }: editor has ${ edBlocks.length }, email has ${ emBlocks.length }.`
			);
		}
		for (
			let i = 0;
			i < Math.min( edBlocks.length, emBlocks.length );
			i++
		) {
			for ( const [ metric ] of boxMetrics ) {
				const delta = +(
					emBlocks[ i ][ metric ] - edBlocks[ i ][ metric ]
				).toFixed( 1 );
				if ( delta === 0 ) {
					continue;
				}
				rows.push( {
					block: `${ type }[${ i }]`,
					metric,
					editor: edBlocks[ i ][ metric ],
					email: emBlocks[ i ][ metric ],
					delta,
					ok: null,
				} );
			}
		}
	}

	const failures = rows.filter( ( r ) => r.ok === false );
	return { rows, warnings, failures, pairs, bands, global };
}
