/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { createElement, useMemo } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { DisplayState } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

export function Edit( {
	attributes,
}: {
	attributes: BlockAttributes & {
		mustMatch: Record< string, Array< string > >;
	};
} ) {
	const blockProps = useBlockProps();
	const { mustMatch } = attributes;

	const productId = useEntityId( 'postType', 'product' );
	const product: Product = useSelect( ( select ) =>
		select( 'core' ).getEditedEntityRecord(
			'postType',
			'product',
			productId
		)
	);

	const displayBlocks = useMemo( () => {
		for ( const [ prop, values ] of Object.entries( mustMatch ) ) {
			if ( ! values.includes( product[ prop ] ) ) {
				return false;
			}
		}
		return true;
	}, [ mustMatch, product ] );

	return (
		<div { ...blockProps }>
			<DisplayState
				state={ displayBlocks ? 'visible' : 'visually-hidden' }
			>
				<InnerBlocks templateLock="all" />
			</DisplayState>
		</div>
	);
}
