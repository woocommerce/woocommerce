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
import { useWatch, useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { EditProductLinkModal } from '../../shared/edit-product-link-modal';
import { getErrorMessageProps } from '~/products/utils/get-error-message-props';

export const DetailsNameField = ( {} ) => {
	const [ showProductLinkEditModal, setShowProductLinkEditModal ] =
		useState( false );
	const { formState, setValue, control, getValues } =
		useFormContext2< Product >();
	const [ id, name, sku, slug, permalink ] = useWatch( {
		name: [ 'id', 'name', 'sku', 'slug', 'permalink' ],
		control,
	} );

	const { field, fieldState } = useController( {
		name: 'name',
		control,
		rules: {
			required: {
				value: true,
				message: __( 'This field is required.', 'woocommerce' ),
			},
			maxLength: {
				value: 120,
				message: __(
					'Please enter a product name shorter than 120 characters.',
					'woocommerce'
				),
			},
		},
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
			<TextControl
				{ ...field }
				{ ...getErrorMessageProps( fieldState ) }
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
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				onBlur={ () => {
					field.onBlur();
					setSkuIfEmpty();
				} }
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
