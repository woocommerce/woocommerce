/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { OPTIONS_STORE_NAME, PartialProduct } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { __experimentalUseProductHelper as useProductHelper } from '@woocommerce/product-editor';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

export const ShippingDimensionsWeightField = () => {
	const { getInputProps } = useFormContext< PartialProduct >();
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

	const inputWeightProps = getInputProps( 'weight', {
		sanitize: ( value: PartialProduct[ keyof PartialProduct ] ) =>
			parseNumber( String( value ) ),
	} );

	return (
		<BaseControl
			id="product_shipping_weight"
			className={ inputWeightProps.className }
			help={ inputWeightProps.help }
		>
			<InputControl
				{ ...inputWeightProps }
				value={ formatNumber( String( inputWeightProps.value ) ) }
				label={ __( 'Weight', 'woocommerce' ) }
				suffix={ weightUnit }
			/>
		</BaseControl>
	);
};
