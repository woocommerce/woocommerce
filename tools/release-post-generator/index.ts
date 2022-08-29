/**
 * External dependencies
 */
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { program } from './program';
import './commands/release-post';
import { checkEnv } from './lib/env-checker';

// Bootstrap environment variables.
dotenv.config();
checkEnv();

// Start the program
program.parse();
