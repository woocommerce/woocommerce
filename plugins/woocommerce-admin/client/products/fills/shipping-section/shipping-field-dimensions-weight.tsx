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

export const ShippingDimensionsWeightField = () => {
	const { control } = useFormContext2< PartialProduct >();
	const { field } = useController( {
		name: 'weight',
		control,
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

	return (
		<BaseControl
			id="product_shipping_weight"
			// className={ inputWeightProps.className } TODO       these props were provided by the old form
			// help={ inputWeightProps.help }
		>
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
