/**
 * External dependencies
 */
import { Block, BlockConfiguration } from '@wordpress/blocks';

/**
 * External dependencies
 */
import { registerWooBlockType } from '@woocommerce/block-templates';

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Partial< BlockConfiguration< T > >;
}

export function registerProductEditorBlockType<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( block: BlockRepresentation< T > ): Block< T > | undefined {
	const { metadata, settings, name } = block;

	const augmentedMetadata = {
		...metadata,
		usesContext: [ ...( metadata.usesContext || [] ), 'editedProduct' ],
	};

	return registerWooBlockType( {
		name,
		metadata: augmentedMetadata,
		settings,
	} );
}
