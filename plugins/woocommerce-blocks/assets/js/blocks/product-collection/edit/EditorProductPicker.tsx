/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Icon, info } from '@wordpress/icons';
import { useEffect, useState } from '@wordpress/element';
import { __experimentalProductSelect as ProductSelect } from '@woocommerce/product-editor';
import { getProducts } from '@woocommerce/editor-components/utils';
import { Product } from '@woocommerce/data';
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

const getSearchResult = async ( search = '' ) => {
	const query = {
		queryArgs: {
			search,
			// Limit search to 40 results. If results are not satisfying
			// user needs to type more characters to get closer to actual
			// product name.
			per_page: 40,
		},
	};
	return ( await getProducts( query ) ) as Product[];
};

const EditorProductPicker = ( props: ProductCollectionEditComponentProps ) => {
	const [ selectedProduct, setSelectedProduct ] = useState( null );
	const [ searchResults, setSearchResults ] = useState< Product[] >( [] );
	const blockProps = useBlockProps();
	// const attributes = props.attributes;

	console.log( 'searchResults', searchResults );

	useEffect( () => {
		getSearchResult().then( ( results ) => {
			setSearchResults( results as Product[] );
		} );
	}, [] );

	const collection = getCollectionByName( props.attributes.collection );
	if ( ! collection ) {
		return;
	}

	const handleSelect = ( product ) => {
		setSelectedProduct( product );
	};

	const handleFilter = async ( search?: string ) => {
		return getSearchResult( search );
	};

	console.log( 'ProductSelect', ProductSelect );

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
				{ /* <ProductControl
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
				/> */ }
				{ /* <ProductSelect /> */ }
				<ProductSelect
					items={ searchResults }
					filter={ handleFilter }
					selected={ null }
					onSelect={ handleSelect }
				/>
			</Placeholder>
		</div>
	);
};

export default EditorProductPicker;
