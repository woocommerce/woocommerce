/**
 * External dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import { useFormContext } from '@woocommerce/components';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import {
	useState,
	createElement,
	createInterpolateElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PRODUCT_DETAILS_SLUG } from '../../constants';
import { EditProductLinkModal } from '../edit-product-link-modal';
import { useProductHelper } from '../../hooks/use-product-helper';

export const DetailsNameField = ( {} ) => {
	const { updateProductWithStatus } = useProductHelper();
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const { getInputProps, values, touched, errors, setValue, resetForm } =
		useFormContext< Product >();

	const { permalinkPrefix, permalinkSuffix } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getPermalinkParts } = select( PRODUCTS_STORE_NAME );
			if ( values.id ) {
				const parts = getPermalinkParts( values.id );
				return {
					permalinkPrefix: parts?.prefix,
					permalinkSuffix: parts?.suffix,
				};
			}
			return {};
		}
	);

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
				label={ createInterpolateElement(
					__( 'Name <required />', 'woocommerce' ),
					{
						required: (
							<span className="woocommerce-product-form__optional-input">
								{ __( '(required)', 'woocommerce' ) }
							</span>
						),
					}
				) }
				name={ `${ PRODUCT_DETAILS_SLUG }-name` }
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				{ ...getInputProps( 'name', {
					onBlur: setSkuIfEmpty,
				} ) }
			/>
			{ values.id && ! hasNameError() && permalinkPrefix && (
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
						onClick={ () => setShowProductLinkEditModal( true ) }
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
					saveHandler={ async ( slug ) => {
						const updatedProduct = await updateProductWithStatus(
							values.id,
							{
								slug,
							},
							values.status,
							true
						);
						if ( updatedProduct && updatedProduct.id ) {
							// only reset the updated slug and permalink fields.
							resetForm(
								{
									...values,
									slug: updatedProduct.slug,
									permalink: updatedProduct.permalink,
								},
								touched,
								errors
							);

							return {
								slug: updatedProduct.slug,
								permalink: updatedProduct.permalink,
							};
						}
					} }
				/>
			) }
		</div>
	);
};
