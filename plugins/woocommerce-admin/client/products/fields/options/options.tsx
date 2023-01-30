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
			onAdd={ () => {
				recordEvent( 'product_add_options_modal_add_button_click' );
			} }
			onChange={ handleChange }
			onNewModalCancel={ () => {
				recordEvent( 'product_add_options_modal_cancel_button_click' );
			} }
			onNewModalOpen={ () => {
				if ( ! attributes.length ) {
					recordEvent( 'product_add_first_option_button_click' );
					return;
				}
				recordEvent( 'product_add_option_button' );
			} }
			uiStrings={ {
				emptyStateSubtitle: __( 'No options yet', 'woocommerce' ),
				newAttributeListItemLabel: __( 'Add option', 'woocommerce' ),
				newAttributeModalTitle: __( 'Add options', 'woocommerce' ),
				globalAttributeHelperMessage: __(
					`You can change the option's name in {{link}}Attributes{{/link}}.`,
					'woocommerce'
				),
			} }
			onRemove={ () =>
				recordEvent(
					'product_remove_option_confirmation_confirm_click'
				)
			}
			onRemoveCancel={ () =>
				recordEvent( 'product_remove_option_confirmation_cancel_click' )
			}
		/>
	);
};
