/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import deprecated from '@wordpress/deprecated';
import { createElement } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { DisplayState } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';

export interface ConditionalBlockAttributes extends BlockAttributes {
	mustMatch: Record< string, Array< string > >;
}

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< ConditionalBlockAttributes > ) {
	deprecated( '`woocommerce/conditional` block', {
		alternative: '`hideConditions` attribute on any block',
	} );

	const { postType } = context;
	const blockProps = useWooBlockProps( attributes );
	const { mustMatch } = attributes;

	const productId = useEntityId( 'postType', postType );

	const displayBlocks = useSelect(
		( select ) => {
			// @ts-expect-error getEditedEntityRecord's curried form strips its generic, returning a wide entity union; Product is the correct narrow type.
			const product: Product = select( 'core' ).getEditedEntityRecord(
				'postType',
				postType,
				productId
			);

			for ( const [ prop, values ] of Object.entries( mustMatch ) ) {
				// @ts-expect-error Indexing Product with a dynamic string key is not supported until @woocommerce/data's Product type adds an index signature.
				if ( ! values.includes( product[ prop ] ) ) {
					return false;
				}
			}
			return true;
		},
		[ postType, productId, mustMatch ]
	);

	return (
		<DisplayState
			{ ...blockProps }
			state={ displayBlocks ? 'visible' : 'visually-hidden' }
		>
			<InnerBlocks templateLock="all" />
		</DisplayState>
	);
}
