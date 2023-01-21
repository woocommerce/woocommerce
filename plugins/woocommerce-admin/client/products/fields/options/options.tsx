/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Product, ProductAttribute } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { AttributeControl } from '../attribute-control';
import { useProductAttributes } from '~/products/hooks/use-product-attributes';
import { useProductVariationsHelper } from '../../hooks/use-product-variations-helper';

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
	const { values } = useFormContext< Product >();
	const { generateProductVariations } = useProductVariationsHelper();

	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: value,
		isVariationAttributes: true,
		onChange: ( newAttributes ) => {
			onChange( newAttributes );
			generateProductVariations( {
				...values,
				attributes: newAttributes,
			} );
		},
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
				recordEvent( 'product_add_option_button' );
			} }
			text={ {
				addAttributeModalTitle: __( 'Add options', 'woocommerce' ),
				emptyStateSubtitle: __( 'No options yet', 'woocommerce' ),
				newAttributeListItemLabel: __( 'Add option', 'woocommerce' ),
				globalAttributeHelperMessage: __(
					`You can change the option's name in {{link}}Attributes{{/link}}.`,
					'woocommerce'
				),
			} }
		/>
	);
};
