import { resolveComposeFile } from './env.mjs';

/**
 * Runs once before the test files start, which inherit the environment variable.
 */
export async function globalSetup() {
	process.env.MCP_WP_ENV_COMPOSE_FILE ??= await resolveComposeFile();
}
