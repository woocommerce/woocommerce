/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Icon, info } from '@wordpress/icons';
import ProductControl from '@woocommerce/editor-components/product-control';
import { SelectedOption } from '@woocommerce/block-hocs';
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

const ProductPicker = ( props: ProductCollectionEditComponentProps ) => {
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
					selected={
						attributes.query?.productReference as SelectedOption
					}
					showVariations
					onChange={ ( value = [] ) => {
						const isValidId = ( value[ 0 ]?.id ?? null ) !== null;
						if ( isValidId ) {
							props.setAttributes( {
								query: {
									...attributes.query,
									productReference: value[ 0 ].id,
								},
							} );
						}
					} }
					messages={ {
						search: __( 'Select a product', 'woocommerce' ),
					} }
				/>
			</Placeholder>
		</div>
	);
};

export default ProductPicker;
