/**
 * External dependencies
 */
import { promisify } from 'util';
import { exec } from 'child_process';

export const execAsync = promisify( exec );
