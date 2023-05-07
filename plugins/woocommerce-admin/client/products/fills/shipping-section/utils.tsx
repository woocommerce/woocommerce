/**
 * External dependencies
 */
import interpolateComponents from '@automattic/interpolate-components';

export const getInterpolatedSizeLabel = ( mixedString: string ) => {
	return interpolateComponents( {
		mixedString,
		components: {
			span: <span className="woocommerce-product-form__secondary-text" />,
		},
	} );
};
