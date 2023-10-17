/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { createElement, useMemo } from '@wordpress/element';
import { InnerBlocks } from '@wordpress/block-editor';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { DisplayState } from '@woocommerce/components';

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
	const blockProps = useWooBlockProps( attributes );
	const { mustMatch } = attributes;

	const product = context.editedProduct;

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
