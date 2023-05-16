/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes } from '../types';
import ColumnsControl from './columns-control';

const ProductCollectionInspectorControls = (
	props: BlockEditProps< ProductCollectionAttributes >
) => {
	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'Settings', 'woo-gutenberg-products-block' ) }
			>
				<ColumnsControl { ...props } />
			</PanelBody>
		</InspectorControls>
	);
};

export default ProductCollectionInspectorControls;
