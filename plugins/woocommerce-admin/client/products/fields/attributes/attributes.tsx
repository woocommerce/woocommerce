/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../attribute-control';
import { useProductAttributes } from '~/products/hooks/use-product-attributes';

type AttributesProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
	productId?: number;
};

export const Attributes: React.FC< AttributesProps > = ( {
	value,
	onChange,
	productId,
} ) => {
	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: value,
		onChange,
		productId,
	} );

	return (
		<AttributeControl
			value={ attributes }
			onChange={ handleChange }
			onModalClose={ ( attribute ) => {
				if ( attribute ) {
					return;
				}
				recordEvent( 'product_add_options_modal_cancel_button_click' );
			} }
			onModalOpen={ ( attribute ) => {
				if ( attribute ) {
					return;
				}
				if ( ! attributes.length ) {
					recordEvent( 'product_add_first_attribute_button_click' );
					return;
				}
				recordEvent( 'product_add_attribute_button' );
			} }
		/>
	);
};
