const fs = require( 'fs-extra' );
const path = require( 'path' );
const promptly = require( 'promptly' );
const chalk = require( 'chalk' );

const files = [
	'._gitignore',
	'_README.md',
	'_webpack.config.js',
	'_main.php',
	'_package.json',
	'._eslintrc.js',
	'._prettierrc.json',
	'._wp-env.json',
];
const maybeThrowError = ( error ) => {
	if ( error ) throw error;
};

( async () => {
	console.log( '\n' );
	console.log(
		chalk.yellow(
			'🎉 Welcome to WooCommerce Admin Extension Starter Pack 🎉'
		)
	);
	console.log( '\n' );
	const extensionName = await promptly.prompt(
		chalk.yellow( 'What is the name of your extension?' )
	);

	const extensionSlug = extensionName.replace( / /g, '-' ).toLowerCase();
	const folder = path.join( __dirname, extensionSlug );

	fs.mkdir( folder, maybeThrowError );

	files.forEach( ( file ) => {
		const from = path.join( __dirname, file );
		const to = path.join(
			folder,
			file === '_main.php'
				? `${ extensionSlug }.php`
				: file.replace( '_', '' )
		);

		fs.readFile( from, 'utf8', ( error, data ) => {
			maybeThrowError( error );

			const addSlugs = data.replace(
				/{{extension_slug}}/g,
				extensionSlug
			);
			const result = addSlugs.replace(
				/{{extension_name}}/g,
				extensionName
			);

			fs.writeFile( to, result, 'utf8', maybeThrowError );
		} );
	} );

	fs.copy(
		path.join( __dirname, 'src' ),
		path.join( folder, 'src' ),
		maybeThrowError
	);

	fs.copy( folder, path.join( '../', extensionSlug ), ( error ) => {
		maybeThrowError( error );

		fs.remove( folder, maybeThrowError );
	} );

	process.stdout.write( '\n' );
	console.log(
		chalk.green(
			'Wonderful, your extension has been scaffolded and placed as a sibling directory to this one.'
		)
	);
	process.stdout.write( '\n' );
	console.log(
		chalk.green(
			'Run the following commands from the root of the extension to scaffold a dev environment.'
		)
	);
	process.stdout.write( '\n' );
	console.log( 'wp-env start' );
	console.log( 'pnpm install' );
	console.log( 'pnpm start' );
	process.stdout.write( '\n' );
} )();
