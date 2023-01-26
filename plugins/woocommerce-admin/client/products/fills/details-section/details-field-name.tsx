/**
 * External dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import { useFormContext } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { Product } from '@woocommerce/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../../shared/edit-product-link-modal';
import { PRODUCT_DETAILS_SLUG } from '../constants';
import { getProductPermalinkParts } from '../../utils/get-product-permalink-parts';

export const DetailsNameField = ( {} ) => {
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const { getInputProps, values, touched, errors, setValue } =
		useFormContext< Product >();

	const [ permalinkPrefix, permalinkSuffix ] = getProductPermalinkParts();

	const hasNameError = () => {
		return Boolean( touched.name ) && Boolean( errors.name );
	};

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
			{ ( values.name || values.slug ) &&
				! hasNameError() &&
				permalinkPrefix && (
					<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
						{ __( 'Product link', 'woocommerce' ) }
						:&nbsp;
						<a
							href={ values.permalink }
							target="_blank"
							rel="noreferrer"
						>
							{ permalinkPrefix }
							{ values.slug || cleanForSlug( values.name ) }
							{ permalinkSuffix }
						</a>
						<Button
							variant="link"
							onClick={ () =>
								setShowProductLinkEditModal( true )
							}
						>
							{ __( 'Edit', 'woocommerce' ) }
						</Button>
					</span>
				) }
			{ showProductLinkEditModal && (
				<EditProductLinkModal
					permalinkPrefix={ permalinkPrefix || '' }
					permalinkSuffix={ permalinkSuffix || '' }
					product={ values }
					onCancel={ () => setShowProductLinkEditModal( false ) }
					onSaved={ () => setShowProductLinkEditModal( false ) }
				/>
			) }
		</div>
	);
};
