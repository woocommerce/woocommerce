/**
 * External dependencies
 */
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { program } from './program';
import './commands/release-post';

// Bootstrap environment variables.
dotenv.config();

// Start the program
program.parse( process.argv );
