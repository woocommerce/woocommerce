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
import { readFile } from 'fs/promises';
import { join } from 'path';

export type TemplateChangeDescription = {
	filePath: string;
	code: string;
	// We could probably move message out into linter later
	message: string;
	pullRequests: number[];
};

const getFileVersion = async (
	repositoryPath: string,
	filePath: string
): Promise< string > => {
	const currentFileContent = await readFile(
		join( repositoryPath, filePath ),
		{ encoding: 'utf8' }
	);
	if ( currentFileContent ) {
		const versionMatch = currentFileContent.match(
			/@version\s+(\d+\.\d+\.\d+)/
		);
		if ( versionMatch ) {
			return versionMatch[ 1 ];
		}
	}
	return '';
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

	const matchPatches = /^a\/(.+)\/templates\/(.+\.php)/g;
	const patches = getPatches( content, matchPatches );
	const matchVersion = `^(\\+.+\\*.+)(@version)\\s+(${ version.replace(
		/\./g,
		'\\.'
	) }).*`;

	const versionRegex = new RegExp( matchVersion, 'g' );
	const deletedRegex = new RegExp( '^deleted file mode [0-9]+' );

	for ( const p in patches ) {
		const patch = patches[ p ];
		const lines = patch.split( '\n' );
		const filePath = getFilename( lines[ 0 ] );
		const pullRequests: number[] = [];

		let lineNumber = 1;
		let passVersionBumpCheck = false;
		let code = 'warning';
		let message = `This template may require a version bump! Expected ${ version }`;

		for ( const l in lines ) {
			const line = lines[ l ];

			if ( line.match( deletedRegex ) ) {
				passVersionBumpCheck = true;
				code = 'notice';
				message = 'Template deleted';
				break;
			}

			if ( line.match( versionRegex ) ) {
				passVersionBumpCheck = true;
				code = 'notice';
				message = 'Version bump found (diff)';
				break;
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

		if ( ! passVersionBumpCheck && repositoryPath ) {
			// The version can be already bumped in the file, but not part of this specific diff.
			const fileVersion = await getFileVersion(
				repositoryPath,
				filePath
			);
			if ( fileVersion === version ) {
				code = 'notice';
				message = 'Version bump found (file)';
			}
		}

		changes.set( filePath, { code, message, filePath, pullRequests } );
	}

	return changes;
};
