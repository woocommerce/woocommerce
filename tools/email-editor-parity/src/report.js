const esc = ( s ) =>
	String( s )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' );

const px = ( v ) => `${ Math.round( Math.abs( v ) ) }px`;

const swatch = ( color ) =>
	color && color !== 'none'
		? `<span class="swatch" style="background:${ esc( color ) }"></span>`
		: '';

function describeBox( box ) {
	const size = `${ Math.round( box.width ) }×${ Math.round( box.height ) }px`;
	const noun = box.kind === 'image' ? 'image' : 'box';
	const parts = [];
	if ( box.background !== 'none' ) {
		parts.push( `${ swatch( box.background ) } background` );
	}
	const [ t, r, b, l ] = box.borders;
	if ( t || r || b || l ) {
		if ( t === r && r === b && b === l ) {
			parts.push( `${ t }px border` );
		} else {
			const sides = [ 'top', 'right', 'bottom', 'left' ].filter(
				( _, i ) => box.borders[ i ] > 0
			);
			parts.push( `border on the ${ sides.join( ' and ' ) }` );
		}
	}
	return `${ size } ${ noun }${
		parts.length ? ` with ${ parts.join( ' and ' ) }` : ''
	}`;
}

const alignWord = ( a ) => ( a === 'center' ? 'centered' : `${ a }-aligned` );

function pairSentences( pair ) {
	const out = [];
	const byMetric = Object.fromEntries(
		pair.issues.map( ( i ) => [ i.metric, i ] )
	);

	if ( byMetric.missing ) {
		out.push(
			pair.editor.kind === 'image'
				? 'is an image in the editor, but the email has no image at a matching position and size.'
				: 'has a background or border in the editor, but no matching painted box exists in the email.'
		);
		return out;
	}
	if ( byMetric.left ) {
		const d = byMetric.left.delta;
		out.push(
			`sits ${ px( d ) } more to the ${
				d > 0 ? 'right' : 'left'
			} in the email (${ byMetric.left.editor }px → ${
				byMetric.left.email
			}px from the left edge).`
		);
	} else if ( byMetric.right ) {
		const d = byMetric.right.delta;
		out.push(
			`sits ${ px( d ) } ${
				d > 0 ? 'further from' : 'closer to'
			} the right edge in the email.`
		);
	}
	if ( byMetric.width ) {
		const d = byMetric.width.delta;
		out.push(
			`is ${ px( d ) } ${ d > 0 ? 'wider' : 'narrower' } in the email (${
				byMetric.width.editor
			}px → ${ byMetric.width.email }px).`
		);
	}
	if ( byMetric.top ) {
		const d = byMetric.top.localDelta ?? byMetric.top.delta;
		out.push(
			`sits ${ px( d ) } ${
				d > 0 ? 'lower' : 'higher'
			} in the email — the vertical gap above this element ${
				d > 0 ? 'grows' : 'shrinks'
			} by that amount.`
		);
	}
	if ( byMetric.height ) {
		const d = byMetric.height.delta;
		out.push(
			`is ${ px( d ) } ${ d > 0 ? 'taller' : 'shorter' } in the email (${
				byMetric.height.editor
			}px → ${ byMetric.height.email }px).`
		);
	}
	for ( const side of [ 'top', 'right', 'bottom', 'left' ] ) {
		const issue = byMetric[ `border-${ side }` ];
		if ( issue ) {
			out.push(
				`has a ${ issue.editor }px ${ side } border in the editor but ${ issue.email }px in the email.`
			);
		}
	}
	if ( byMetric[ 'text-align' ] ) {
		out.push(
			`has ${ alignWord(
				byMetric[ 'text-align' ].editor
			) } text in the editor but ${ alignWord(
				byMetric[ 'text-align' ].email
			) } text in the email.`
		);
	}
	if ( byMetric.background ) {
		out.push(
			`changes background color: ${ swatch(
				byMetric.background.editor
			) } ${ esc( byMetric.background.editor ) } in the editor, ${ swatch(
				byMetric.background.email
			) } ${ esc( byMetric.background.email ) } in the email.`
		);
	}
	return out;
}

function globalSentence( row ) {
	if ( row.metric === 'content width' ) {
		return `The email content area is ${ px( row.delta ) } ${
			row.delta > 0 ? 'wider' : 'narrower'
		} than the editor canvas (${ row.editor }px → ${
			row.email
		}px). Something inside is pushing the layout ${
			row.delta > 0 ? 'apart' : 'together'
		}.`;
	}
	if ( row.metric === 'horizontal overflow' ) {
		return `The ${
			row.surface
		} content overflows its container horizontally by ${ px(
			row.delta
		) } — content is wider than the available space.`;
	}
	if ( row.metric === 'painted box count' ) {
		return `The email paints ${
			row.editor - row.email
		} fewer boxes with a background or border than the editor (${
			row.editor
		} → ${
			row.email
		}). Some elements lose their visual styling — they are marked on the editor screenshot below.`;
	}
	return `${ row.block } ${ row.metric }: editor ${ row.editor }, email ${ row.email }.`;
}

function marker( box, dims, num, side ) {
	if ( ! box || ! dims.width || ! dims.height ) {
		return '';
	}
	const l = ( box.left / dims.width ) * 100;
	const t = ( box.top / dims.height ) * 100;
	const w = ( box.width / dims.width ) * 100;
	const h = ( box.height / dims.height ) * 100;
	return `<div class="marker" style="left:${ l }%;top:${ t }%;width:${ w }%;height:${ h }%" title="Finding ${ num } (${ side })"><span>${ num }</span></div>`;
}

function analyzeFixture( r ) {
	const pairs = r.comparison.pairs ?? [];
	const failingPairs = pairs.filter( ( p ) =>
		p.issues.some( ( i ) => i.ok === false )
	);
	const infoPairs = pairs.filter(
		( p ) => p.issues.length && ! p.issues.some( ( i ) => i.ok === false )
	);
	const globalIssues = ( r.comparison.global ?? [] ).filter(
		( g ) => g.ok === false
	);
	const bands = r.comparison.bands ?? [];

	// Pairs whose failing metrics have the same deltas are one story told
	// many times (e.g. every cell of a shifted table) — group them into a
	// single finding.
	const groups = [];
	const bySig = new Map();
	for ( const p of failingPairs ) {
		const sig =
			( p.editor.kind ?? 'painted' ) +
			'|' +
			p.issues
				.filter( ( i ) => i.ok === false )
				.map(
					( i ) =>
						`${ i.metric }:${
							i.delta === null
								? i.email
								: Math.round(
										i.metric === 'top'
											? i.localDelta ?? i.delta
											: i.delta
								  )
						}`
				)
				.join( '|' );
		if ( ! bySig.has( sig ) ) {
			bySig.set( sig, [] );
			groups.push( bySig.get( sig ) );
		}
		bySig.get( sig ).push( p );
	}

	return {
		pairs,
		failingPairs,
		infoPairs,
		globalIssues,
		bands,
		groups,
		findingCount: groups.length + bands.length + globalIssues.length,
		failed: r.comparison.failures.length > 0,
	};
}

function findingsSection( r, a ) {
	const joinSentences = ( sentences, subject ) =>
		sentences
			.map( ( s, i ) => ( i === 0 ? s : `${ subject } ${ s }` ) )
			.join( ' ' );

	const items = [];
	const editorMarks = [];
	const emailMarks = [];
	let num = 0;

	for ( const g of a.globalIssues ) {
		items.push(
			`<li class="finding fail"><span class="where">Whole email</span> ${ globalSentence(
				g
			) }</li>`
		);
	}
	for ( const band of a.bands ) {
		num++;
		const what =
			band.count === 1
				? 'This element'
				: `A row of ${ band.count } elements`;
		items.push(
			`<li class="finding fail"><span class="num">${ num }</span> <span class="where">${ what }</span> is ${ alignWord(
				band.editorAlign
			) } in the editor but ${ alignWord(
				band.emailAlign
			) } in the email.</li>`
		);
		editorMarks.push( [ band.editorBox, num ] );
		emailMarks.push( [ band.emailBox, num ] );
	}
	for ( const group of a.groups ) {
		num++;
		const first = group[ 0 ];
		const sentences = pairSentences( first );
		const plural = first.editor.kind === 'image' ? 'images' : 'boxes';
		const where =
			group.length === 1
				? describeBox( first.editor )
				: `${
						group.length
				  } similar ${ plural } (like the ${ describeBox(
						first.editor
				  ) })`;
		const body =
			group.length === 1
				? joinSentences( sentences, 'It' )
				: `Each ${ joinSentences( sentences, 'Each' ) }`;
		items.push(
			`<li class="finding fail"><span class="num">${ num }</span> <span class="where">${ where }</span> ${ body }</li>`
		);
		for ( const p of group ) {
			editorMarks.push( [ p.editor, num ] );
			if ( p.email ) {
				emailMarks.push( [ p.email, num ] );
			}
		}
	}
	for ( const p of a.infoPairs ) {
		items.push(
			`<li class="finding info"><span class="where">${ describeBox(
				p.editor
			) }</span> ${ joinSentences(
				pairSentences( p ),
				'It'
			) } <em>(informational)</em></li>`
		);
	}

	const okCount = a.pairs.length - a.failingPairs.length;
	const summary =
		a.findingCount === 0
			? `<p class="all-good">✓ All ${ a.pairs.length } measured boxes (backgrounds, borders and images) match between the editor and the email, and nothing overflows.</p>`
			: `<p class="findings-intro">${ okCount } of ${ a.pairs.length } measured boxes match. Findings:</p>`;

	let annotated = '';
	if ( editorMarks.length || emailMarks.length ) {
		const editorMarkers = editorMarks
			.map( ( [ box, n ] ) =>
				marker( box, r.diff.sizes.editor, n, 'editor' )
			)
			.join( '' );
		const emailMarkers = emailMarks
			.map( ( [ box, n ] ) =>
				marker( box, r.diff.sizes.email, n, 'email' )
			)
			.join( '' );
		annotated = `
		<div class="annotated">
			<figure><figcaption>Editor — findings marked</figcaption>
				<div class="shot"><img src="${ esc( r.files.editor ) }">${ editorMarkers }</div>
			</figure>
			<figure><figcaption>Email — findings marked</figcaption>
				<div class="shot"><img src="${ esc( r.files.email ) }">${ emailMarkers }</div>
			</figure>
		</div>`;
	}

	return `${ summary }${
		items.length ? `<ul class="findings">${ items.join( '' ) }</ul>` : ''
	}${ annotated }`;
}

function diagnosticsSection( r ) {
	const warnings = [ ...r.warnings, ...r.comparison.warnings ]
		.map( ( w ) => `<li>${ esc( w ) }</li>` )
		.join( '' );

	const rawRows = r.comparison.rows
		.map( ( row ) => {
			const cls =
				row.ok === false ? 'fail' : row.ok === null ? 'info' : '';
			return `<tr class="${ cls }"><td>${ esc(
				row.block
			) }</td><td>${ esc( row.metric ) }</td><td>${ esc(
				row.editor
			) }</td><td>${ esc( row.email ) }</td><td>${
				row.delta === null ? '—' : row.delta
			}</td></tr>`;
		} )
		.join( '' );

	return `
	<details class="diagnostics">
		<summary>Diagnostics — raw measurements (${ r.comparison.rows.length } rows)${
			warnings ? ' and block-mapping notes' : ''
		}</summary>
		${ warnings ? `<ul class="warnings">${ warnings }</ul>` : '' }
		<table>
			<thead><tr><th>Element</th><th>Metric</th><th>Editor</th><th>Email</th><th>Δ</th></tr></thead>
			<tbody>${ rawRows }</tbody>
		</table>
	</details>`;
}

function fixtureSection( r, a ) {
	const status = a.failed ? 'fail' : 'pass';
	const stats = [
		a.findingCount
			? `${ a.findingCount } finding${ a.findingCount === 1 ? '' : 's' }`
			: 'no findings',
		`${ r.diff.diffPct }% pixel diff`,
	].join( ' · ' );

	return `
<details class="fixture" id="fx-${ esc( r.name ) }"${ a.failed ? ' open' : '' }>
	<summary>
		<span class="badge ${ status }">${ status.toUpperCase() }</span>
		<span class="name">${ esc( r.name ) }</span>
		<span class="stats">${ stats }</span>
		<span class="chev">▸</span>
	</summary>
	<div class="fixture-body">
		<p class="meta">
			<a class="btn" href="${ esc(
				r.editorUrl
			) }" target="_blank" rel="noopener">Open in editor (post #${
				r.postId
			})</a>
			<a class="btn" href="${ esc(
				r.previewUrl
			) }" target="_blank" rel="noopener">Open rendered preview</a>
		</p>

		${ findingsSection( r, a ) }

		<h4>Visual comparison</h4>
		<div class="tabs">
			<label><input type="radio" name="tab-${ esc(
				r.name
			) }" checked><span>Side by side</span></label>
			<label><input type="radio" name="tab-${ esc(
				r.name
			) }"><span>Overlay</span></label>
			<label><input type="radio" name="tab-${ esc(
				r.name
			) }"><span>Pixel diff</span></label>
		</div>
		<div class="panels">
			<div class="panel side-by-side">
				<figure><figcaption>Editor canvas</figcaption><img src="${ esc(
					r.files.editor
				) }"></figure>
				<figure><figcaption>Rendered email</figcaption><img src="${ esc(
					r.files.email
				) }"></figure>
			</div>
			<div class="panel overlay">
				<div class="overlay-stack">
					<img src="${ esc( r.files.editor ) }">
					<img src="${ esc( r.files.email ) }" class="overlay-top">
				</div>
				<label class="opacity">Rendered email opacity
					<input type="range" min="0" max="100" value="50"
						oninput="this.closest('.overlay').querySelector('.overlay-top').style.opacity=this.value/100">
				</label>
			</div>
			<div class="panel">
				<figure><figcaption>Pixel diff (${
					r.diff.diffPct
				}%)</figcaption><img src="${ esc( r.files.diff ) }"></figure>
			</div>
		</div>

		${ diagnosticsSection( r ) }
	</div>
</details>`;
}

export function buildReport( runId, results ) {
	const analyzed = results.map( ( r ) => [ r, analyzeFixture( r ) ] );
	// Failing fixtures first — they are what the reader came for.
	analyzed.sort(
		( [ , a ], [ , b ] ) => Number( b.failed ) - Number( a.failed )
	);

	const failed = analyzed.filter( ( [ , a ] ) => a.failed ).length;
	const overviewRows = analyzed
		.map( ( [ r, a ] ) => {
			const status = a.failed ? 'fail' : 'pass';
			return `<tr>
				<td><span class="badge ${ status }">${ status.toUpperCase() }</span></td>
				<td><a href="#fx-${ esc( r.name ) }">${ esc( r.name ) }</a></td>
				<td>${ a.findingCount || '—' }</td>
				<td>${ r.diff.diffPct }%</td>
			</tr>`;
		} )
		.join( '' );

	const sections = analyzed
		.map( ( [ r, a ] ) => fixtureSection( r, a ) )
		.join( '\n' );

	return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Email parity report ${ esc( runId ) }</title>
<style>
	:root { --red: #cc1818; --green: #00845b; --line: #e2e4e7; --muted: #646970; }
	* { box-sizing: border-box; }
	body { font: 14px/1.55 -apple-system, "Segoe UI", Roboto, sans-serif; margin: 0;
		background: #f6f7f7; color: #1e1e1e; }
	.wrap { max-width: 1150px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
	h1 { font-size: 1.35rem; margin: 0 0 0.2rem; }
	.sub { color: var(--muted); margin: 0 0 1.5rem; }
	a { color: #2271b1; }

	.overview { width: 100%; background: #fff; border: 1px solid var(--line);
		border-radius: 10px; border-collapse: separate; border-spacing: 0; overflow: hidden; }
	.overview th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em;
		color: var(--muted); }
	.overview th, .overview td { padding: 9px 16px; border-bottom: 1px solid var(--line);
		text-align: left; }
	.overview tr:last-child td { border-bottom: none; }
	.overview a { text-decoration: none; font-weight: 600; color: inherit; }
	.overview a:hover { color: #2271b1; }

	.badge { padding: 2px 8px; border-radius: 4px; color: #fff; font-size: 0.7rem;
		font-weight: 700; letter-spacing: 0.03em; }
	.badge.pass { background: var(--green); }
	.badge.fail { background: var(--red); }

	details.fixture { background: #fff; border: 1px solid var(--line); border-radius: 10px;
		margin: 1rem 0; overflow: hidden; }
	details.fixture > summary { list-style: none; cursor: pointer; display: flex;
		align-items: center; gap: 0.75rem; padding: 0.85rem 1.25rem; }
	details.fixture > summary::-webkit-details-marker { display: none; }
	details.fixture > summary .name { font-weight: 650; font-size: 1.02rem; }
	details.fixture > summary .stats { color: var(--muted); font-size: 0.85rem; }
	details.fixture > summary .chev { margin-left: auto; color: var(--muted);
		transition: transform 0.15s; }
	details.fixture[open] > summary .chev { transform: rotate(90deg); }
	details.fixture[open] > summary { border-bottom: 1px solid var(--line); }
	details.fixture > summary:hover { background: #fafbfb; }
	.fixture-body { padding: 1rem 1.25rem 1.5rem; }
	.fixture-body h4 { margin: 1.5rem 0 0.5rem; font-size: 0.95rem; }
	.meta { margin: 0.2rem 0 1rem; display: flex; gap: 0.5rem; }
	.btn { display: inline-block; padding: 5px 14px; border: 1px solid #c5c9cd;
		border-radius: 6px; background: #fff; color: #2271b1; text-decoration: none;
		font-weight: 500; }
	.btn:hover { background: #f0f6fb; border-color: #2271b1; }

	.all-good { color: var(--green); font-weight: 600; }
	.findings-intro { margin-bottom: 0.4rem; }
	.findings { list-style: none; padding: 0; margin: 0.4rem 0 1rem; }
	.finding { padding: 0.5rem 0.75rem; margin: 0.35rem 0; border-radius: 6px; }
	.finding.fail { background: #fcebeb; }
	.finding.info { background: #f0f4fa; color: #444; }
	.finding .num { display: inline-flex; align-items: center; justify-content: center;
		width: 20px; height: 20px; border-radius: 50%; background: var(--red); color: #fff;
		font-size: 0.75rem; font-weight: 700; margin-right: 4px; }
	.finding .where { font-weight: 600; }
	.swatch { display: inline-block; width: 12px; height: 12px; border-radius: 3px;
		border: 1px solid #999; vertical-align: -1px; }

	.annotated { display: flex; gap: 1rem; margin: 1rem 0; }
	.annotated figure { margin: 0; flex: 1; min-width: 0; }
	.shot { position: relative; display: inline-block; max-width: 100%; }
	.shot img { display: block; max-width: 100%; border: 1px solid var(--line);
		border-radius: 4px; }
	.marker { position: absolute; border: 2px solid var(--red); border-radius: 2px;
		box-shadow: 0 0 0 1px rgba(255,255,255,0.7); pointer-events: none; }
	.marker span { position: absolute; top: -9px; left: -9px; width: 18px; height: 18px;
		border-radius: 50%; background: var(--red); color: #fff; font-size: 0.7rem;
		font-weight: 700; display: flex; align-items: center; justify-content: center; }

	.tabs { display: inline-flex; background: #eef0f1; border-radius: 8px; padding: 3px;
		margin: 0.25rem 0 0.75rem; }
	.tabs label { cursor: pointer; }
	.tabs input { display: none; }
	.tabs span { display: inline-block; padding: 4px 14px; border-radius: 6px;
		color: #50575e; }
	.tabs label:has(input:checked) span { background: #fff; color: #1e1e1e;
		box-shadow: 0 1px 2px rgba(0,0,0,0.18); }
	.panels .panel { display: none; }
	.side-by-side { gap: 1rem; }
	.side-by-side figure { margin: 0; min-width: 0; flex: 1; }
	.side-by-side img, .panel img { max-width: 100%; border: 1px solid var(--line);
		border-radius: 4px; }
	figcaption { font-weight: 600; margin-bottom: 4px; }
	/* Grid stacking: the container is as tall as the taller image, so a
	   longer email screenshot cannot cover the opacity slider below. */
	.overlay-stack { display: inline-grid; }
	.overlay-stack img { grid-area: 1 / 1; display: block; align-self: start;
		border: 1px solid var(--line); border-radius: 4px; }
	.overlay-top { opacity: 0.5; }
	.opacity { display: block; margin-top: 0.5rem; color: var(--muted); }

	details.diagnostics { margin: 1.25rem 0 0; }
	details.diagnostics summary { cursor: pointer; color: var(--muted); }
	.warnings { background: #fcf9e8; border: 1px solid #f0e6b3; border-radius: 6px;
		padding: 0.5rem 2rem; margin: 0.75rem 0; }
	table { border-collapse: collapse; margin: 0.75rem 0; }
	th, td { border: 1px solid var(--line); padding: 4px 10px; text-align: left; }
	tr.fail td { background: #fcebeb; }
	tr.info td { background: #f0f4fa; color: #444; }
</style>
</head>
<body>
<div class="wrap">
<h1>Email parity report</h1>
<p class="sub">${ esc( runId ) } · ${ results.length } fixtures · ${
		failed ? `${ failed } failed` : 'all passed'
	}</p>
<table class="overview">
	<thead><tr><th>Status</th><th>Fixture</th><th>Findings</th><th>Pixel diff</th></tr></thead>
	<tbody>${ overviewRows }</tbody>
</table>
${ sections }
</div>
<script>
// Tab switching: show the panel matching the checked radio index.
document.querySelectorAll('.fixture').forEach((fixture) => {
	const radios = fixture.querySelectorAll('.tabs input');
	const panels = fixture.querySelectorAll('.panels .panel');
	const update = () => {
		radios.forEach((radio, i) => {
			panels[i].style.display = radio.checked ? (i === 0 ? 'flex' : 'block') : 'none';
		});
	};
	radios.forEach((r) => r.addEventListener('change', update));
	update();
});
// Opening an overview link also expands the collapsed fixture.
document.querySelectorAll('.overview a').forEach((link) => {
	link.addEventListener('click', () => {
		const target = document.querySelector(link.getAttribute('href'));
		if (target) target.open = true;
	});
});
</script>
</body>
</html>`;
}
