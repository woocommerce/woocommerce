/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { cleanForSlug } from '@wordpress/url';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../shared/edit-product-link-modal';
import { useProductAutoSave } from '../hooks/use-product-auto-save';

export const ProductLinkField = () => {
	const { errors, touched, values } = useFormContext< Product >();
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const hasNameError = Boolean( touched.name ) && Boolean( errors.name );
	const { isAutoSaving } = useProductAutoSave( [ 'name' ] );

	const { permalinkPrefix, permalinkSuffix } = useSelect(
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

	if ( ! values.name && ! values.slug ) {
		return null;
	}

	if ( isAutoSaving ) {
		return <Spinner />;
	}

	if ( hasNameError || ! permalinkPrefix ) {
		return null;
	}

	return (
		<>
			<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
				{ __( 'Product link', 'woocommerce' ) }
				:&nbsp;
				<a href={ values.permalink } target="_blank" rel="noreferrer">
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
			{ showProductLinkEditModal && (
				<EditProductLinkModal
					permalinkPrefix={ permalinkPrefix || '' }
					permalinkSuffix={ permalinkSuffix || '' }
					product={ values }
					onCancel={ () => setShowProductLinkEditModal( false ) }
					onSaved={ () => setShowProductLinkEditModal( false ) }
				/>
			) }
		</>
	);
};
