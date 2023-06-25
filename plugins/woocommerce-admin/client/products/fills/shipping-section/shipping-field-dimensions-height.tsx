/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { PartialProduct } from '@woocommerce/data';
import { __experimentalUseProductHelper as useProductHelper } from '@woocommerce/product-editor';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getInterpolatedSizeLabel } from './utils';
import { ShippingDimensionsPropsType } from './index';

export const ShippingDimensionsHeightField = ( {
	dimensionProps,
	setHighlightSide,
}: ShippingDimensionsPropsType ) => {
	const { getInputProps } = useFormContext< PartialProduct >();
	const { formatNumber } = useProductHelper();

	const inputHeightProps = getInputProps(
		'dimensions.height',
		dimensionProps
	);
	return (
		<BaseControl
			id="product_shipping_dimensions_height"
			className={ inputHeightProps.className }
			help={ inputHeightProps.help }
		>
			<InputControl
				{ ...inputHeightProps }
				value={ formatNumber( String( inputHeightProps.value ) ) }
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
