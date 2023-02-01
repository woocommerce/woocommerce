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

export const ShippingDimensionsHeightField = ( {
	dimensionProps,
	setHighlightSide,
}: ShippingDimensionsPropsType ) => {
	const { control } = useFormContext2< PartialProduct >();
	const { field } = useController( {
		name: 'dimensions.height',
		control,
	} );
	const { formatNumber } = useProductHelper();
	return (
		<BaseControl
			id="product_shipping_dimensions_height"
			// className={ inputHeightProps.className } TODO	   these props were provided by the old form
			// help={ inputHeightProps.help }
		>
			<InputControl
				{ ...field }
				{ ...dimensionProps }
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
