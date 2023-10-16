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

function useEvaluationContext( context: Record< string, unknown > ) {
	const { productType } = context;

	const productId = useEntityId( 'postType', productType );

	const getEvaluationContext = ( select: typeof WPSelect ) => {
		const editedProduct = select( 'core' ).getEditedEntityRecord(
			'postType',
			productType,
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

export function registerProductEditorBlockType<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( block: BlockRepresentation< T > ): Block< T > | undefined {
	const { metadata, settings, name } = block;

	const augmentedMetadata = {
		...metadata,
		usesContext: [ ...( metadata.usesContext || [] ), 'productType' ],
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
