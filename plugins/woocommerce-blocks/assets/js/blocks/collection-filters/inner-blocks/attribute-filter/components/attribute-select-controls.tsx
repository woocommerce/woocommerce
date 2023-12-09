/**
 * External dependencies
 */
import { sort } from 'fast-sort';
import { __, sprintf, _n } from '@wordpress/i18n';
import { SearchListControl } from '@woocommerce/editor-components/search-list-control';
import { getSetting } from '@woocommerce/settings';
import { SearchListItem } from '@woocommerce/editor-components/search-list-control/types';
import { AttributeSetting } from '@woocommerce/types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

type AttributeSelectControlsProps = {
	isCompact: boolean;
	setAttributeId: ( id: number ) => void;
	attributeId: number;
};

export const AttributeSelectControls = ( {
	isCompact,
	setAttributeId,
	attributeId,
}: AttributeSelectControlsProps ) => {
	const messages = {
		clear: __( 'Clear selected attribute', 'woo-gutenberg-products-block' ),
		list: __( 'Product Attributes', 'woo-gutenberg-products-block' ),
		noItems: __(
			"Your store doesn't have any product attributes.",
			'woo-gutenberg-products-block'
		),
		search: __(
			'Search for a product attribute:',
			'woo-gutenberg-products-block'
		),
		selected: ( n: number ) =>
			sprintf(
				/* translators: %d is the number of attributes selected. */
				_n(
					'%d attribute selected',
					'%d attributes selected',
					n,
					'woo-gutenberg-products-block'
				),
				n
			),
		updated: __(
			'Product attribute search results updated.',
			'woo-gutenberg-products-block'
		),
	};

	const list = sort(
		ATTRIBUTES.map( ( item ) => {
			return {
				id: parseInt( item.attribute_id, 10 ),
				name: item.attribute_label,
			};
		} )
	).asc( 'name' );

	const onChange = ( selected: SearchListItem[] ) => {
		if ( ! selected || ! selected.length ) {
			return;
		}

		const selectedId = selected[ 0 ].id;
		const productAttribute = ATTRIBUTES.find(
			( attribute ) => attribute.attribute_id === selectedId.toString()
		);

		if ( ! productAttribute || attributeId === selectedId ) {
			return;
		}

		setAttributeId( selectedId as number );
	};

	return (
		<SearchListControl
			className="woocommerce-product-attributes"
			list={ list }
			selected={ list.filter( ( { id } ) => id === attributeId ) }
			onChange={ onChange }
			messages={ messages }
			isSingle
			isCompact={ isCompact }
		/>
	);
};
