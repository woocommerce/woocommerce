/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { getAffectedCommand } from './commands/get-affected';
import { syncPushCommand } from './commands/sync-push';
import { tagReleaseCommand } from './commands/tag-release';

const program = new Command( 'prod-repo' )
	.description( 'Utilities for syncing prod-repo and tagging a release.' )
	.addCommand( getAffectedCommand )
	.addCommand( syncPushCommand )
	.addCommand( tagReleaseCommand );

export default program;
