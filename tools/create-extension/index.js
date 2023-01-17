const fs = require( 'fs-extra' );
const path = require( 'path' );
const promptly = require( 'promptly' );
const chalk = require( 'chalk' );
const program = require( 'commander' );

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

program.option( '-e, --example [example]', 'Create extension with example' );

program.parse( process.argv );

const options = program.opts();

( async () => {
	console.log( '\n' );
	console.log(
		chalk.yellow(
			'🎉 Welcome to WooCommerce Admin Extension Starter Pack 🎉'
		)
	);
	console.log( '\n' );
	let extensionName = '';
	let isExample = false;
	if ( ! options.example ) {
		extensionName = await promptly.prompt(
			chalk.yellow( 'What is the name of your extension?' )
		);
	} else {
		extensionName = options.example;
		const extensionPath = path.join(
			__dirname,
			`examples/${ extensionName }/src/index.js`
		);

		if ( ! fs.existsSync( extensionPath ) ) {
			throw new Error( 'Extension example does not exist.' );
		} else {
			isExample = true;
		}
	}

	const extensionSlug =
		extensionName.replace( / /g, '-' ).toLowerCase() +
		( isExample ? '-example' : '' );
	const folder = path.join( __dirname, extensionSlug );

	fs.mkdirSync( folder, maybeThrowError );

	files.forEach( ( file ) => {
		let from = path.join( __dirname, file );
		if (
			isExample &&
			fs.existsSync(
				path.join( __dirname, `examples/${ extensionName }`, file )
			)
		) {
			from = path.join( __dirname, `examples/${ extensionName }`, file );
		}
		const to = path.join(
			folder,
			file === '_main.php'
				? `${ extensionSlug }.php`
				: file.replace( '_', '' )
		);

		try {
			const data = fs.readFileSync( from, 'utf8' );

			const addSlugs = data.replace(
				/{{extension_slug}}/g,
				extensionSlug
			);
			const result = addSlugs.replace(
				/{{extension_name}}/g,
				extensionName
			);

			fs.writeFileSync( to, result, 'utf8' );
		} catch ( error ) {
			maybeThrowError( error );
		}
	} );

	const fromSrcPath = [ __dirname ];
	if ( isExample ) {
		fromSrcPath.push( 'examples', extensionName );
	}

	fs.mkdir( path.join( folder, 'src' ), maybeThrowError );
	try {
		fs.copySync(
			path.join( ...fromSrcPath, 'src' ),
			path.join( folder, 'src' )
		);
	} catch ( e ) {
		maybeThrowError( e );
	}

	if ( fs.existsSync( path.join( ...fromSrcPath, 'includes' ) ) ) {
		fs.mkdirSync( path.join( folder, 'includes' ) );
		try {
			fs.copySync(
				path.join( ...fromSrcPath, 'includes' ),
				path.join( folder, 'includes' )
			);
		} catch ( e ) {
			maybeThrowError( e );
		}
	}

	try {
		fs.moveSync( folder, path.join( '../', extensionSlug ) );
	} catch ( error ) {
		maybeThrowError( error );
	}
	fs.remove( folder, maybeThrowError );

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
