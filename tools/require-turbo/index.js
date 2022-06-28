if ( ! process.env.TURBO_HASH ) {
	console.error( 'This project uses Turborepo. You should not run this script from the project directly.' );
	if ( process.env.npm_lifecycle_event && process.env.npm_package_name ) {
		console.error( "\nTry running the following from the root of the monorepo instead:" );
		console.error( "pnpm -- turbo run %s --filter=%s\n", process.env.npm_lifecycle_event, process.env.npm_package_name );
	}
	process.exit( 1 );
}
