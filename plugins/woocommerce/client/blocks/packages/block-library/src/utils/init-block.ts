/**
 * External dependencies
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

type BlockDefinition< TAttributes extends object = object > = {
	name: string;
	metadata: Record< string, unknown >;
	settings: Partial< BlockConfiguration< TAttributes > >;
};

/**
 * Register an individual block from its metadata and settings.
 *
 * @param block Block definition.
 * @return The registered block type, or undefined when registration fails.
 */
export default function initBlock< TAttributes extends object = object >(
	block: BlockDefinition< TAttributes >
) {
	const { metadata, name, settings } = block;
	return registerBlockType(
		{ name, ...metadata } as unknown as BlockConfiguration< TAttributes >,
		settings
	);
}
