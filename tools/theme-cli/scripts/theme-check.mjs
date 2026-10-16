import { runEnvironment } from './env.mjs';

export async function runThemeCheck( slug ) {
	const run = ( args ) => runEnvironment( slug, args, 'theme-check' );
	try {
		const status = await run( [ 'start', '--runtime=docker' ] );
		if ( status !== 0 ) {
			return status;
		}
		return await run( [
			'run',
			'cli',
			'wp',
			'theme-check',
			'run',
			slug,
			'--exec=WP_CLI::add_hook( "after_wp_load", fn() => add_filter( "theme_scandir_exclusions", fn( $excluded ) => array_merge( $excluded, [ "artifacts", "composer.json", "composer.lock", "package.json", "phpcs.xml.dist" ] ) ) );',
		] );
	} finally {
		if ( ( await run( [ 'stop' ] ) ) !== 0 ) {
			throw new Error(
				`Could not stop the Theme Check environment for ${ slug }.`
			);
		}
	}
}
