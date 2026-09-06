#!/usr/bin/env node

const { spawnSync } = require( 'node:child_process' );
const path = require( 'node:path' );

const failure = ( message ) => {
	process.stderr.write( `FAIL: ${ message }\n` );
	return 1;
};

const getFiles = () => {
	if ( process.env.DOC_LINK_FILES_JSON === undefined ) {
		return process.argv.slice( 2 );
	}

	try {
		const files = JSON.parse( process.env.DOC_LINK_FILES_JSON );
		if (
			! Array.isArray( files ) ||
			files.some( ( file ) => typeof file !== 'string' )
		) {
			return null;
		}
		return files;
	} catch {
		return null;
	}
};

const files = getFiles();
if ( files === null ) {
	process.exitCode = failure(
		'DOC_LINK_FILES_JSON must be a JSON array of strings'
	);
} else if ( files.length > 0 ) {
	const config = path.resolve( __dirname, '../check-doc-links-config.json' );
	const result = spawnSync(
		'pnpm',
		[
			'dlx',
			'markdown-link-check@3.14.2',
			'--quiet',
			'--config',
			config,
			'--',
			...files,
		],
		{ shell: false, stdio: 'inherit' }
	);

	process.exitCode = result.error
		? failure( `unable to start link checker: ${ result.error.message }` )
		: result.status ?? 1;
}
