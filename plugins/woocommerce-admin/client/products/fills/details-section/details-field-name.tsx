/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import interpolateComponents from '@automattic/interpolate-components';
import { Product } from '@woocommerce/data';
import { TextControl } from '@wordpress/components';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { PRODUCT_DETAILS_SLUG } from '../constants';
import { ProductLinkField } from '~/products/components/product-link-field';

export const DetailsNameField = ( {} ) => {
	const { getInputProps, values, setValue } = useFormContext< Product >();

	const setSkuIfEmpty = () => {
		if ( values.sku || ! values.name?.length ) {
			return;
		}
		setValue( 'sku', cleanForSlug( values.name ) );
	};
	return (
		<div>
			<TextControl
				label={ interpolateComponents( {
					mixedString: __( 'Name {{required/}}', 'woocommerce' ),
					components: {
						required: (
							<span className="woocommerce-product-form__optional-input">
								{ __( '(required)', 'woocommerce' ) }
							</span>
						),
					},
				} ) }
				name={ `${ PRODUCT_DETAILS_SLUG }-name` }
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				{ ...getInputProps( 'name', {
					onBlur: setSkuIfEmpty,
				} ) }
			/>
			<ProductLinkField />
		</div>
	);
};
