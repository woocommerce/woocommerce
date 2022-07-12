/**
 * Internal dependencies
 */
import {
	getFilename,
	getHookChangeType,
	getHookDescription,
	getHookName,
	getPatches,
	getVersionRegex,
} from './utils';

export const scanHooks = (
	content: string,
	version: string,
	output: string,
	onFinish: () => void,
	onError: ( err: string ) => void
): Map< string, Map< string, string[] > > => {
	const report: Map< string, Map< string, string[] > > = new Map<
		string,
		Map< string, string[] >
	>();

	if ( ! content.match( /diff --git a\/(.+).php/g ) ) {
		// CliUx.ux.action.stop();
		onFinish();
		return report;
	}

	const matchPatches = /^a\/(.+).php/g;
	const patches = getPatches( content, matchPatches );
	const verRegEx = getVersionRegex( version );
	const matchHooks = `\(.*?)@since\\s+(${ verRegEx })(.*?)(apply_filters|do_action)\\((\\s+)?(\\'|\\")(.*?)(\\'|\\")`;
	const newRegEx = new RegExp( matchHooks, 'gs' );

	for ( const p in patches ) {
		const patch = patches[ p ];
		// Separate patches into bits beginning with a comment. If a bit does not have an action, disregard.
		const patchWithHook = patch.split( '/**' ).find( ( s ) => {
			return s.includes( 'apply_filters' ) || s.includes( 'do_action' );
		} );
		if ( ! patchWithHook ) {
			continue;
		}
		const results = patchWithHook.match( newRegEx );
		const hooksList: Map< string, string[] > = new Map<
			string,
			string[]
		>();

		if ( ! results ) {
			continue;
		}

		const lines = patch.split( '\n' );
		const filepath = getFilename( lines[ 0 ] );

		for ( const raw of results ) {
			// Extract hook name and type.
			const hookName = raw.match(
				/(.*)(do_action|apply_filters)\(\s+'(.*)'/
			);

			if ( ! hookName ) {
				continue;
			}

			const name = getHookName( hookName[ 3 ] );
			const description = getHookDescription( raw, name );

			if ( ! description ) {
				onError(
					`Hook ${ name } has no description. Please add a description.`
				);
				// this.error(
				// 	`Hook ${ name } has no description. Please add a description.`
				// );
			} else {
				const kind =
					hookName[ 2 ] === 'do_action' ? 'action' : 'filter';
				const CLIMessage = `**${ name }** introduced in ${ version }`;
				const GithubMessage = `\\'${ name }\\' introduced in ${ version }`;
				const message =
					output === 'github' ? GithubMessage : CLIMessage;
				const hookChangeType = getHookChangeType( raw );
				const title = `${ hookChangeType } ${ kind } found`;

				if ( ! hookName[ 2 ].startsWith( '-' ) ) {
					hooksList.set( name, [
						'NOTICE',
						title,
						message,
						description,
					] );
				}
			}
		}

		report.set( filepath, hooksList );
	}

	// CliUx.ux.action.stop();
	onFinish();
	return report;
};
