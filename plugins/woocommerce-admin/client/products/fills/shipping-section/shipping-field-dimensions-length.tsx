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

export const ShippingDimensionsLengthField = ( {
	dimensionProps,
	setHighlightSide,
}: ShippingDimensionsPropsType ) => {
	const { getInputProps } = useFormContext< PartialProduct >();
	const { formatNumber } = useProductHelper();

	const inputLengthProps = getInputProps(
		'dimensions.length',
		dimensionProps
	);
	return (
		<BaseControl
			id="product_shipping_dimensions_length"
			className={ inputLengthProps.className }
			help={ inputLengthProps.help }
		>
			<InputControl
				{ ...inputLengthProps }
				value={ formatNumber( String( inputLengthProps.value ) ) }
				label={ getInterpolatedSizeLabel(
					__( 'Length {{span}}B{{/span}}', 'woocommerce' )
				) }
				onFocus={ () => {
					setHighlightSide( 'B' );
				} }
			/>
		</BaseControl>
	);
};
