/**
 * Internal dependencies
 */
import { program } from '../program';

// Define the release post command
program
	.command( 'beta' )
	.description( 'CLI to automate generation of a beta release post.' );
