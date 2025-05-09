/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Icon, info } from '@wordpress/icons';
import ProductControl from '@woocommerce/editor-components/product-control';
import type { SelectedOption } from '@woocommerce/block-hocs';
import { createInterpolateElement } from '@wordpress/element';
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

const ProductPicker = (
	props: ProductCollectionEditComponentProps & {
		isDeletedProductReference: boolean;
	}
) => {
	const blockProps = useBlockProps();
	const { attributes, isDeletedProductReference } = props;

	const collection = getCollectionByName( attributes.collection );
	if ( ! collection ) {
		return null;
	}

	const infoText = isDeletedProductReference
		? __(
				'Previously selected product is no longer available.',
				'woocommerce'
		  )
		: createInterpolateElement(
				sprintf(
					/* translators: %s: collection title */
					__(
						'<strong>%s</strong> requires a product to be selected in order to display associated items.',
						'woocommerce'
					),
					collection.title
				),
				{ strong: <strong /> }
		  );

	return (
		<div { ...blockProps }>
			<Placeholder className="wc-blocks-product-collection__editor-product-picker">
				<HStack alignment="center">
					<Icon
						icon={ info }
						className="wc-blocks-product-collection__info-icon"
					/>
					<Text>{ infoText }</Text>
				</HStack>
				<ProductControl
					selected={
						attributes.query?.productReference as SelectedOption
					}
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
