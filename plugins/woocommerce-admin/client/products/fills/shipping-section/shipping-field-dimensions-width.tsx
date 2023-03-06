/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { PartialProduct } from '@woocommerce/data';
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

export const ShippingDimensionsWidthField = ( {
	dimensionProps,
	setHighlightSide,
}: ShippingDimensionsPropsType ) => {
	const { getInputProps } = useFormContext< PartialProduct >();
	const { formatNumber } = useProductHelper();

	const inputWidthProps = getInputProps( 'dimensions.width', dimensionProps );

	return (
		<BaseControl
			id="product_shipping_dimensions_width"
			className={ inputWidthProps.className }
			help={ inputWidthProps.help }
		>
			<InputControl
				{ ...inputWidthProps }
				value={ formatNumber( String( inputWidthProps.value ) ) }
				label={ getInterpolatedSizeLabel(
					__( 'Width {{span}}A{{/span}}', 'woocommerce' )
				) }
				onFocus={ () => {
					setHighlightSide( 'A' );
				} }
			/>
		</BaseControl>
	);
};
