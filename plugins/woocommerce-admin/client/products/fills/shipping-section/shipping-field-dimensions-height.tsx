/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { PartialProduct } from '@woocommerce/data';
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
import { getInterpolatedSizeLabel } from './utils';
import { ShippingDimensionsPropsType } from './index';
import { getErrorMessageProps } from '~/products/utils/get-error-message-props';

export const ShippingDimensionsHeightField = ( {
	dimensionProps,
	setHighlightSide,
}: ShippingDimensionsPropsType ) => {
	const { control } = useFormContext2< PartialProduct >();
	const { field, fieldState } = useController( {
		name: 'dimensions.height',
		control,
		rules: {
			min: {
				value: 0,
				message: __(
					'Height must be higher than zero.',
					'woocommerce'
				),
			},
		},
	} );
	const { formatNumber } = useProductHelper();
	const errorProps = getErrorMessageProps( fieldState );
	return (
		<BaseControl id="product_shipping_dimensions_height" { ...errorProps }>
			<InputControl
				{ ...field }
				{ ...dimensionProps }
				onBlur={ () => {
					dimensionProps.onBlur();
					field.onBlur();
				} }
				value={ formatNumber( String( field.value ) ) }
				label={ getInterpolatedSizeLabel(
					__( 'Height {{span}}C{{/span}}', 'woocommerce' )
				) }
				onFocus={ () => {
					setHighlightSide( 'C' );
				} }
			/>
		</BaseControl>
	);
};
