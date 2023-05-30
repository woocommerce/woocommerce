/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import ProductAttributeTermControl from '@woocommerce/editor-components/product-attribute-term-control';
import { AttributeMetadata } from '@woocommerce/types';
import { SearchListItem } from '@woocommerce/editor-components/search-list-control/types';
import { ADMIN_URL } from '@woocommerce/settings';
import {
	ExternalLink,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ProductCollectionQuery } from '../types';

const EDIT_ATTRIBUTES_URL = `${ ADMIN_URL }edit.php?post_type=product&page=product_attributes`;

interface AttributesControlProps {
	woocommerceAttributes?: AttributeMetadata[];
	setQueryAttribute: ( value: Partial< ProductCollectionQuery > ) => void;
}

const AttributesControl = ( {
	woocommerceAttributes,
	setQueryAttribute,
}: AttributesControlProps ) => {
	const selectedAttributes = woocommerceAttributes?.map(
		( { termId: id } ) => ( {
			id,
		} )
	);

	return (
		<ToolsPanelItem
			label={ __( 'Product Attributes', 'woo-gutenberg-products-block' ) }
			hasValue={ () => !! woocommerceAttributes?.length }
		>
			<ProductAttributeTermControl
				messages={ {
					search: __( 'Attributes', 'woo-gutenberg-products-block' ),
				} }
				selected={ selectedAttributes || [] }
				onChange={ ( searchListItems: SearchListItem[] ) => {
					const newValue = searchListItems.map(
						( { id, value } ) => ( {
							termId: id as number,
							taxonomy: value as string,
						} )
					);

					setQueryAttribute( {
						woocommerceAttributes: newValue,
					} );
				} }
				operator={ 'any' }
				isCompact={ true }
				type={ 'token' }
			/>
			<ExternalLink
				className="wc-block-editor-product-collection-panel__manage-attributes-link"
				href={ EDIT_ATTRIBUTES_URL }
			>
				{ __( 'Manage attributes', 'woo-gutenberg-products-block' ) }
			</ExternalLink>
		</ToolsPanelItem>
	);
};

export default AttributesControl;
