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

export const ShippingDimensionsWidthField = ( {
	dimensionProps,
	setHighlightSide,
}: ShippingDimensionsPropsType ) => {
	const { control } = useFormContext2< PartialProduct >();
	const { formatNumber } = useProductHelper();

	const { field } = useController( {
		name: 'dimensions.width',
		control,
	} );

	return (
		<BaseControl
			id="product_shipping_dimensions_width"
			// className={ dimensionProps.className }
			// help={ inputWidthProps.help } TODO       these props were provided by the old form
		>
			<InputControl
				{ ...field }
				{ ...dimensionProps }
				value={ formatNumber( String( field.value ) ) }
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
