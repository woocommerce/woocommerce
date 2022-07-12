/**
 * External dependencies
 */
import { CliUx, Command } from '@oclif/core';

/**
 * Internal dependencies
 */
import { generateAPatch } from '../../git';

/**
 * Analyzer class
 */
export default class Analyzer extends Command {
	/**
	 * CLI description
	 */
	static description = 'Test commands being developed.';

	/**
	 * CLI arguments
	 */
	static args = [];

	/**
	 * CLI flags.
	 */
	static flags = {};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		CliUx.ux.action.start( `Generating a patch` );

		// Do the thing
		await generateAPatch(
			'/Users/samseay/Code/automattic/woo/woocommerce',
			'9c242bcc4c28b2c9d06a2985a5f9e4596d7b1eb8',
			'0240a29eb637b5fa3b76467e41dca4522a2a0c89'
		);

		CliUx.ux.action.stop();
	}
}
