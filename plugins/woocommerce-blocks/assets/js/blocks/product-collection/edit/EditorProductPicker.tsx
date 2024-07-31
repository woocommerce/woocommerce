/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Icon, info } from '@wordpress/icons';
import ProductControl from '@woocommerce/editor-components/product-control';
import {
	Placeholder,
	// @ts-expect-error Using experimental features
	__experimentalHStack as HStack,
	// @ts-expect-error Using experimental features
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ProductCollectionEditComponentProps } from '../types';
import { getCollectionByName } from '../collections';

const EditorProductPicker = ( props: ProductCollectionEditComponentProps ) => {
	const blockProps = useBlockProps();
	const attributes = props.attributes;

	const collection = getCollectionByName( props.attributes.collection );
	if ( ! collection ) {
		return;
	}

	return (
		<div { ...blockProps }>
			<Placeholder className="wc-blocks-product-collection__editor-product-picker">
				<HStack alignment="center">
					<Icon
						icon={ info }
						className="wc-blocks-product-collection__info-icon"
					/>
					<Text>
						<strong>{ collection.title }</strong> requires a product
						to be selected in order to display associated items.
					</Text>
				</HStack>
				<ProductControl
					selected={ attributes.selectedReference?.id || 0 }
					showVariations
					onChange={ ( value = [] ) => {
						const id = value[ 0 ] ? value[ 0 ].id : 0;
						props.setAttributes( {
							selectedReference: {
								type: 'product',
								id,
							},
						} );
					} }
				/>
			</Placeholder>
		</div>
	);
};

export default EditorProductPicker;
