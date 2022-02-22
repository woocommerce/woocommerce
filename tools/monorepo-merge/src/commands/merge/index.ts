/**
 * External dependencies
 */
import { Command } from '@oclif/core';

export default class Merge extends Command {
	static description =
		'Merges another repository into this one with history.';

	static args = [
		{
			name: 'source',
			description: 'The GitHub repository we are merging from.',
			required: true,
		},
		{
			name: 'destination',
			description:
				'The local destination for the repository to be merged at.',
			required: true,
		},
	];

	async run(): Promise< void > {
		const { args } = await this.parse( Merge );

		this.log( 'It has begun' );
	}
}
