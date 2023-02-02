/**
 * External dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import { useFormContext2 } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { useState } from '@wordpress/element';
import { Controller, useWatch } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../../shared/edit-product-link-modal';
import { PRODUCT_DETAILS_SLUG } from '../constants';

export const DetailsNameField = ( {} ) => {
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const { formState, setValue, control, getValues } =
		useFormContext2< Product >();
	const [ id, name, sku, slug, permalink ] = useWatch( {
		name: [ 'id', 'name', 'sku', 'slug', 'permalink' ],
		control,
	} );

	const { permalinkPrefix, permalinkSuffix } = useSelect(
		( select: WCDataSelector ) => {
			const { getPermalinkParts } = select( PRODUCTS_STORE_NAME );
			if ( id ) {
				const parts = getPermalinkParts( id );
				return {
					permalinkPrefix: parts?.prefix,
					permalinkSuffix: parts?.suffix,
				};
			}
			return {};
		}
	);

	const hasNameError = () => {
		return (
			Boolean( formState.touchedFields.name ) &&
			Boolean( formState.errors.name )
		);
	};

	const setSkuIfEmpty = () => {
		if ( sku || ! name?.length ) {
			return;
		}
		setValue( 'sku', cleanForSlug( name ) );
	};
	return (
		<div>
			<Controller
				name="name"
				control={ control }
				render={ ( { field } ) => (
					<TextControl
						{ ...field }
						label={ interpolateComponents( {
							mixedString: __(
								'Name {{required/}}',
								'woocommerce'
							),
							components: {
								required: (
									<span className="woocommerce-product-form__optional-input">
										{ __( '(required)', 'woocommerce' ) }
									</span>
								),
							},
						} ) }
						placeholder={ __(
							'e.g. 12 oz Coffee Mug',
							'woocommerce'
						) }
						onBlur={ setSkuIfEmpty }
					/>
				) }
			/>
			{ id && ! hasNameError() && permalinkPrefix && (
				<span className="woocommerce-product-form__secondary-text product-details-section__product-link">
					{ __( 'Product link', 'woocommerce' ) }
					:&nbsp;
					<a href={ permalink } target="_blank" rel="noreferrer">
						{ permalinkPrefix }
						{ slug || cleanForSlug( name ) }
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
					product={ getValues() as Product }
					onCancel={ () => setShowProductLinkEditModal( false ) }
					onSaved={ () => setShowProductLinkEditModal( false ) }
				/>
			) }
		</div>
	);
};
