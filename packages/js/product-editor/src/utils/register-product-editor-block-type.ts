/**
 * External dependencies
 */
import { Block, BlockConfiguration } from '@wordpress/blocks';
import { select as WPSelect } from '@wordpress/data';
import { registerWooBlockType } from '@woocommerce/block-templates';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Partial< BlockConfiguration< T > >;
}

export function useEvaluationContext( context: Record< string, unknown > ) {
	const { postType } = context;

	const productId = useEntityId( 'postType', postType );

	const getEvaluationContext = ( select: typeof WPSelect ) => {
		const editedProduct = select( 'core' ).getEditedEntityRecord(
			'postType',
			postType,
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
