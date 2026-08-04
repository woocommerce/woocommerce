import { mkdir, readdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { config } from '../config.js';
import {
	launchBrowser,
	login,
	captureEditor,
	capturePreview,
} from './capture.js';
import { compareGeometry } from './geometry.js';
import { diffImages } from './diff.js';
import { buildReport } from './report.js';
import {
	checkEnvironment,
	enableEmailEditorFeature,
	seedEmailPost,
	getPreviewUrl,
	deleteEmailPost,
} from './wp.js';

const rootDir = path.dirname(
	path.dirname( fileURLToPath( import.meta.url ) )
);
const fixturesDir = path.join( rootDir, 'fixtures' );

async function loadFixtures( filters ) {
	const files = ( await readdir( fixturesDir ) ).filter( ( f ) =>
		f.endsWith( '.html' )
	);
	const selected = filters.length
		? files.filter( ( f ) =>
				filters.some( ( name ) => f.includes( name ) )
		  )
		: files;
	if ( ! selected.length ) {
		throw new Error(
			`No fixtures matched ${ JSON.stringify(
				filters
			) } in ${ fixturesDir }`
		);
	}
	return Promise.all(
		selected.map( async ( file ) => ( {
			name: path.basename( file, '.html' ),
			markup: await readFile( path.join( fixturesDir, file ), 'utf8' ),
		} ) )
	);
}

async function main() {
	const args = process.argv.slice( 2 );
	const cleanup = args.includes( '--cleanup' );
	const filters = args.filter( ( a ) => ! a.startsWith( '--' ) );

	await checkEnvironment();
	await enableEmailEditorFeature();
	const fixtures = await loadFixtures( filters );

	const runId = new Date()
		.toISOString()
		.replace( /[:.]/g, '-' )
		.slice( 0, 19 );
	const runDir = path.join( rootDir, 'out', runId );
	await mkdir( runDir, { recursive: true } );

	const { browser, context } = await launchBrowser();
	await login( context );

	const results = [];
	try {
		for ( const fixture of fixtures ) {
			console.log( `\n▶ ${ fixture.name }` );
			const postId = await seedEmailPost(
				`Parity: ${ fixture.name }`,
				fixture.markup
			);
			const previewUrl = await getPreviewUrl( postId );
			const editorUrl = `${ config.baseUrl }/wp-admin/post.php?post=${ postId }&action=edit`;
			console.log( `  seeded post #${ postId }` );

			const editor = await captureEditor( context, postId );
			console.log(
				`  captured editor canvas (${
					editor.geometry.blocks?.length ?? 0
				} blocks)`
			);
			const preview = await capturePreview( context, previewUrl );
			console.log(
				`  captured rendered preview (${
					preview.geometry.blocks?.length ?? 0
				} blocks)`
			);

			const diff = diffImages( editor.png, preview.png );
			const comparison = compareGeometry(
				editor.geometry,
				preview.geometry,
				config.tolerances
			);

			const files = {
				editor: `${ fixture.name }.editor.png`,
				email: `${ fixture.name }.email.png`,
				diff: `${ fixture.name }.diff.png`,
			};
			await writeFile( path.join( runDir, files.editor ), editor.png );
			await writeFile( path.join( runDir, files.email ), preview.png );
			await writeFile( path.join( runDir, files.diff ), diff.diffBuffer );
			await writeFile(
				path.join( runDir, `${ fixture.name }.editor.html` ),
				editor.html
			);
			await writeFile(
				path.join( runDir, `${ fixture.name }.email.html` ),
				preview.html
			);

			results.push( {
				name: fixture.name,
				postId,
				editorUrl,
				previewUrl,
				files,
				diff,
				comparison,
				warnings: editor.warnings,
				editorGeometry: editor.geometry,
				emailGeometry: preview.geometry,
			} );

			const failCount = comparison.failures.length;
			console.log(
				`  pixel diff ${ diff.diffPct }% · geometry ${
					failCount === 0
						? 'PASS'
						: `FAIL (${ failCount } metric(s) off)`
				}`
			);

			if ( cleanup ) {
				await deleteEmailPost( postId );
			}
		}
	} finally {
		await browser.close();
	}

	const reportPath = path.join( runDir, 'report.html' );
	await writeFile( reportPath, buildReport( runId, results ) );
	await writeFile(
		path.join( runDir, 'results.json' ),
		JSON.stringify(
			results.map( ( { diff, ...r } ) => ( {
				...r,
				diff: { ...diff, diffBuffer: undefined },
			} ) ),
			null,
			'\t'
		)
	);

	const failed = results.filter( ( r ) => r.comparison.failures.length > 0 );
	console.log( `\nReport: ${ reportPath }` );
	console.log(
		failed.length
			? `✗ ${ failed.length }/${ results.length } fixture(s) failed geometry checks`
			: `✓ all ${ results.length } fixture(s) within tolerances`
	);
	process.exitCode = failed.length ? 1 : 0;
}

main().catch( ( err ) => {
	console.error( err.message );
	process.exitCode = 1;
} );
