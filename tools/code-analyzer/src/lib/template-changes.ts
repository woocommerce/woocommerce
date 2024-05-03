/**
 * External dependencies
 */
import {
	getFilename,
	getStartingLineNumber,
	getPullRequestNumberFromHash,
	getPatches,
	getLineCommitHash,
} from '@woocommerce/monorepo-utils/src/core/git';
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';

export type TemplateChangeDescription = {
	filePath: string;
	code: string;
	// We could probably move message out into linter later
	message: string;
	pullRequests: number[];
};

export const scanForTemplateChanges = async (
	content: string,
	version: string,
	repositoryPath?: string
) => {
	const changes: Map< string, TemplateChangeDescription > = new Map();

	if ( ! content.match( /diff --git a\/(.+)\/templates\/(.+)\.php/g ) ) {
		return changes;
	}

	const matchPatches = /^a\/(.+)\/templates\/(.+)/g;
	const patches = getPatches( content, matchPatches );
	const matchVersion = `^(\\+.+\\*.+)(@version)\\s+(${ version.replace(
		/\./g,
		'\\.'
	) }).*`;

	const versionRegex = new RegExp( matchVersion, 'g' );

	for ( const p in patches ) {
		const patch = patches[ p ];
		const lines = patch.split( '\n' );
		const filePath = getFilename( lines[ 0 ] );
		const pullRequests = [];

		let lineNumber = 1;
		let code = 'warning';
		let message = 'This template may require a version bump!';

		for ( const l in lines ) {
			const line = lines[ l ];

			if ( line.match( versionRegex ) ) {
				code = 'notice';
				message = 'Version bump found';
			}

			if ( repositoryPath ) {
				// Don't parse the headers for the patch.
				if ( parseInt( l, 10 ) < 4 ) {
					continue;
				}

				if ( line.match( /^@@/ ) ) {
					// If we reach a chunk, update the line number, and then continue.
					lineNumber = getStartingLineNumber( line );
					continue;
				}

				if ( line.match( /^\+/ ) ) {
					try {
						const commitHash = await getLineCommitHash(
							repositoryPath,
							filePath,
							lineNumber
						);
						const prNumber = await getPullRequestNumberFromHash(
							repositoryPath,
							commitHash
						);
						if ( pullRequests.indexOf( prNumber ) === -1 ) {
							pullRequests.push( prNumber );
						}
					} catch ( e: unknown ) {
						Logger.notice(
							`Unable to get PR number in ${ filePath }:${ lineNumber }`
						);
					}
				}

				// We shouldn't increment line numbers for the a-side of the patch.
				if ( ! line.match( /^-/ ) ) {
					lineNumber++;
				}
			}
		}

		changes.set( filePath, { code, message, filePath, pullRequests } );
	}

	return changes;
};
