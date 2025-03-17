/**
 * External dependencies
 */
import { Block, BlockConfiguration } from '@wordpress/blocks';
import { registerWooBlockType } from '@woocommerce/block-templates';
import { useEntityId } from '@wordpress/core-data';

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Partial< BlockConfiguration< T > >;
}

// Define a more generic type for the select function to avoid TypeScript errors
type SelectType = ( store: string ) => Record< string, unknown >;

export function useEvaluationContext( context: Record< string, unknown > ) {
	const { postType } = context;

	const productId = useEntityId( 'postType', postType as string );

	const getEvaluationContext = ( select: SelectType ) => {
		const coreStore = select( 'core' ) as {
			getEditedEntityRecord: (
				kind: string,
				name: string,
				id: number
			) => Record< string, unknown >;
		};

		const editedProduct = coreStore.getEditedEntityRecord(
			'postType',
			postType as string,
			productId
		);

		return {
			...context,
			editedProduct,
		};
	};

	return {
		getEvaluationContext,
	};
}

function augmentUsesContext( usesContext?: string[] ) {
	// Note: If you modify this function, also update the server-side
	// Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry::augment_uses_context() function.
	return [ ...( usesContext || [] ), 'postType' ];
}

export function registerProductEditorBlockType<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( block: BlockRepresentation< T > ): Block< T > | undefined {
	const { metadata, settings, name } = block;

	const augmentedMetadata = {
		...metadata,
		usesContext: augmentUsesContext( metadata.usesContext ),
	};

	return registerWooBlockType(
		{
			name,
			metadata: augmentedMetadata,
			settings,
		},
		useEvaluationContext
	);
}
