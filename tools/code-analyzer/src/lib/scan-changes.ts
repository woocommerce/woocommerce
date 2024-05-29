/**
 * External dependencies
 */
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';
import {
	cloneRepoShallow,
	generateDiff,
} from '@woocommerce/monorepo-utils/src/core/git';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { scanForDBChanges } from './db-changes';
import { scanForHookChanges } from './hook-changes';
import { scanForTemplateChanges } from './template-changes';

export type ScanType = 'db' | 'hooks' | 'templates' | string;

const generateVersionDiff = async (
	compareVersion: string,
	base: string,
	source: string,
	clonedPath?: string
) => {
	Logger.startTask( `Making temporary clone of ${ source }...` );

	const tmpRepoPath =
		typeof clonedPath !== 'undefined'
			? clonedPath
			: await cloneRepoShallow( source );

	Logger.endTask();

	Logger.notice(
		`Temporary clone of ${ source } created at ${ tmpRepoPath }`
	);

	const diff = await generateDiff(
		tmpRepoPath,
		base,
		compareVersion,
		Logger.error,
		[ 'tools' ]
	);

	return { diff, tmpRepoPath };
};

export const scanChangesForDB = async (
	compareVersion: string,
	base: string,
	source: string,
	clonedPath?: string
) => {
	const { diff } = await generateVersionDiff(
		compareVersion,
		base,
		source,
		clonedPath
	);

	return scanForDBChanges( diff );
};

export const scanChangesForHooks = async (
	compareVersion: string,
	sinceVersion: string,
	base: string,
	source: string,
	clonedPath?: string
) => {
	const { diff, tmpRepoPath } = await generateVersionDiff(
		compareVersion,
		base,
		source,
		clonedPath
	);

	const hookChanges = await scanForHookChanges(
		diff,
		sinceVersion,
		tmpRepoPath
	);

	return Array.from( hookChanges.values() );
};

export const scanChangesForTemplates = async (
	compareVersion: string,
	sinceVersion: string,
	base: string,
	source: string,
	clonedPath?: string
) => {
	const { diff, tmpRepoPath } = await generateVersionDiff(
		compareVersion,
		base,
		source,
		clonedPath
	);

	const templateChanges = await scanForTemplateChanges(
		diff,
		sinceVersion,
		tmpRepoPath
	);

	return Array.from( templateChanges.values() );
};

export const scanForChanges = async (
	compareVersion: string,
	sinceVersion: string,
	source: string,
	base: string,
	outputStyle: 'cli' | 'github',
	clonedPath?: string,
	exclude: string[] = []
) => {
	Logger.startTask( `Making temporary clone of ${ source }...` );

	const tmpRepoPath =
		typeof clonedPath !== 'undefined'
			? clonedPath
			: await cloneRepoShallow( source );

	Logger.endTask();

	Logger.notice(
		`Temporary clone of ${ source } created at ${ tmpRepoPath }`
	);

	const diff = await generateDiff(
		tmpRepoPath,
		base,
		compareVersion,
		Logger.error,
		[ 'tools', ...( exclude ? exclude : [] ) ]
	);

	// Only checkout the compare version if we're in CLI mode.
	if ( outputStyle === 'cli' ) {
		execSync(
			`cd ${ tmpRepoPath } && git -c core.hooksPath=/dev/null checkout ${ compareVersion }`,
			{
				stdio: 'pipe',
			}
		);
	}

	Logger.startTask( 'Detecting hook changes...' );
	const hookChanges = await scanForHookChanges(
		diff,
		sinceVersion,
		tmpRepoPath
	);
	Logger.endTask();

	Logger.startTask( 'Detecting template changes...' );
	const templateChanges = await scanForTemplateChanges(
		diff,
		sinceVersion,
		tmpRepoPath
	);
	Logger.endTask();

	Logger.startTask( 'Detecting DB changes...' );
	const dbChanges = scanForDBChanges( diff );
	Logger.endTask();

	return {
		hooks: hookChanges,
		templates: templateChanges,
		db: dbChanges,
	};
};
