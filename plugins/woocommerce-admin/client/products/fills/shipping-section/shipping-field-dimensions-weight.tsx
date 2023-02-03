/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { OPTIONS_STORE_NAME, PartialProduct } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useController } from 'react-hook-form';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useProductHelper } from '../../use-product-helper';
import { getErrorMessageProps } from '~/products/utils/get-error-message-props';

export const ShippingDimensionsWeightField = () => {
	const { control } = useFormContext2< PartialProduct >();
	const { field, fieldState } = useController( {
		name: 'weight',
		control,
		rules: {
			min: {
				value: 0,
				message: __(
					'Weight must be higher than zero.',
					'woocommerce'
				),
			},
		},
	} );

	const { formatNumber, parseNumber } = useProductHelper();

	const { weightUnit, hasResolvedUnits } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		return {
			weightUnit: getOption( 'woocommerce_weight_unit' ),
			hasResolvedUnits: hasFinishedResolution( 'getOption', [
				'woocommerce_weight_unit',
			] ),
		};
	}, [] );

	if ( ! hasResolvedUnits ) {
		return null;
	}

	const errorProps = getErrorMessageProps( fieldState );

	return (
		<BaseControl id="product_shipping_weight" { ...errorProps }>
			<InputControl
				{ ...field }
				sanitize={ ( value: PartialProduct[ keyof PartialProduct ] ) =>
					parseNumber( String( value ) )
				}
				value={ formatNumber( String( field.value ) ) }
				label={ __( 'Weight', 'woocommerce' ) }
				suffix={ weightUnit }
			/>
		</BaseControl>
	);
};
