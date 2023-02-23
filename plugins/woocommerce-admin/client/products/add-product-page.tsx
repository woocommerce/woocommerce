/**
 * External dependencies
 */
import { Editor } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { TextControl } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';

/**
 * Internal dependencies
 */
import './product-page.scss';

const dummyProduct = {
	name: 'Example product',
	short_description: 'Short product description content',
} as Product;

const AddProductPage: React.FC = () => {
	// const [ blocks, setBlocks ] = useState( [] );

	// useEffect( () => {
	// 	function UpdateBlocks() {
	// 		const newBlocks = Array( 100 ).fill( 0 );
	// 		setBlocks( newBlocks as [] );
	// 	}
	// 	Object.defineProperty( UpdateBlocks, 'name', {
	// 		value: 'UpdateBlocks',
	// 		writable: false,
	// 	} );

	// 	const id = setTimeout( UpdateBlocks, 2000 );
	// 	return () => clearTimeout( id );
	// }, [] );

	// return (
	// 	<>
	// 		{ blocks.map( ( _, key ) => (
	// 			<TextControl
	// 				key={ key }
	// 				label={ interpolateComponents( {
	// 					mixedString: __( 'Name {{required/}}', 'woocommerce' ),
	// 					components: {
	// 						required: (
	// 							<span className="woocommerce-product-form__optional-input">
	// 								{ __( '(required)', 'woocommerce' ) }
	// 							</span>
	// 						),
	// 					},
	// 				} ) }
	// 				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
	// 				value={ '' }
	// 				onChange={ () => {} }
	// 			/>
	// 		) ) }
	// 	</>
	// );
	return <Editor product={ dummyProduct } settings={ {} } />;
};

AddProductPage.displayName = 'AddProductPage';

Object.defineProperty( AddProductPage, 'name', {
	value: 'AddProductPage',
	writable: false,
} );

export default AddProductPage;
