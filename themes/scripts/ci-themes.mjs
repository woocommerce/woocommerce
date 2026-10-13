import { execFileSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { discoverThemes, isMain, themesRoot } from './theme-utils.mjs';

export function selectThemes( themes, changedFiles ) {
	const sharedChange =
		changedFiles === null ||
		changedFiles.some(
			( file ) =>
				file.startsWith( 'plugins/woocommerce/' ) ||
				file.startsWith( 'packages/' ) ||
				[
					'package.json',
					'pnpm-lock.yaml',
					'pnpm-workspace.yaml',
					'.nvmrc',
					'.syncpackrc',
					'.github/workflows/themes-ci.yml',
				].includes( file ) ||
				file.startsWith(
					'.github/actions/setup-woocommerce-monorepo/'
				) ||
				( file.startsWith( 'themes/' ) &&
					! themes.some( ( theme ) =>
						file.startsWith( `themes/${ theme.slug }/` )
					) )
		);
	return themes.filter(
		( theme ) =>
			sharedChange ||
			changedFiles.some( ( file ) =>
				file.startsWith( `themes/${ theme.slug }/` )
			)
	);
}

if ( isMain( import.meta.url ) ) {
	const git = ( ...args ) =>
		execFileSync( 'git', args, {
			cwd: path.dirname( themesRoot ),
			encoding: 'utf8',
		} );
	const themes = await discoverThemes();
	const [ base, head = 'HEAD' ] = process.argv.slice( 2 );
	const changedFiles =
		! base || /^0+$/.test( base )
			? null
			: git( 'diff', '--name-only', '-z', `${ base }...${ head }` )
					.split( '\0' )
					.filter( Boolean );
	const selected = selectThemes( themes, changedFiles );
	const json = JSON.stringify( selected );
	console.log( json );
	if ( process.env.GITHUB_OUTPUT ) {
		await fs.appendFile(
			process.env.GITHUB_OUTPUT,
			`themes=${ json }\nhas-themes=${ selected.length > 0 }\n`
		);
	}
}
