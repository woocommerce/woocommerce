/**
 * External dependencies
 */
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
	ProductAttribute,
	PRODUCTS_STORE_NAME,
} from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { AttributeField } from '../attribute-field';

type OptionsProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	productId?: number;
};

export const Options: React.FC< OptionsProps > = ( {
	value,
	onChange,
	productId,
} ) => {
	const { generateProductVariations, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME );
	const { updateProduct } = useDispatch( PRODUCTS_STORE_NAME );

	const handleChange = async ( attributes: ProductAttribute[] ) => {
		onChange( attributes );
		await updateProduct( productId, {
			attributes,
		} );
		await generateProductVariations( { product_id: productId } );
		invalidateResolutionForStoreSelector( 'getProductVariations' );
	};

	return (
		<AttributeField
			attributeType="for-variations"
			value={ value }
			onChange={ handleChange }
			productId={ productId }
		/>
	);
};
