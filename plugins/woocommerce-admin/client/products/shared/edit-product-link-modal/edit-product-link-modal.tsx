/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import { Product } from '@woocommerce/data';
import { Text } from '@woocommerce/experimental';
import { useFormContext } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './edit-product-link-modal.scss';
import { useProductHelper } from '../../use-product-helper';

type EditProductLinkModalProps = {
	product: Product;
	permalinkPrefix: string | undefined;
	permalinkSuffix: string | undefined;
	onCancel: () => void;
	onSaved: () => void;
};

export const EditProductLinkModal: React.FC< EditProductLinkModalProps > = ( {
	product,
	permalinkPrefix,
	permalinkSuffix,
	onCancel,
	onSaved,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const {
		createOrUpdateProductWithStatus,
		isUpdatingDraft,
		isUpdatingPublished,
	} = useProductHelper();
	const [ slug, setSlug ] = useState(
		product.slug || cleanForSlug( product.name )
	);
	const { resetForm, touched, errors } = useFormContext< Product >();

	const onSave = async () => {
		recordEvent( 'product_update_slug', {
			new_product_page: true,
			product_id: product.id,
			product_type: product.type,
		} );
		const updatedProduct = await createOrUpdateProductWithStatus(
			product.id || null,
			{
				slug,
			},
			product.status || 'auto-draft',
			true
		);
		if ( updatedProduct && updatedProduct.id ) {
			// only reset the updated slug and permalink fields.
			resetForm(
				{
					...product,
					id: updatedProduct.id,
					slug: updatedProduct.slug,
					permalink: updatedProduct.permalink,
				},
				touched,
				errors
			);
			createNotice(
				updatedProduct.slug === cleanForSlug( slug )
					? 'success'
					: 'info',
				updatedProduct.slug === cleanForSlug( slug )
					? __( 'Product link successfully updated.', 'woocommerce' )
					: __(
							'Product link already existed, updated to ',
							'woocommerce'
					  ) + updatedProduct.permalink
			);
		} else {
			createNotice(
				'error',
				__( 'Failed to update product link.', 'woocommerce' )
			);
		}
		onSaved();
	};

	const newProductLinkLabel =
		( permalinkPrefix || '' ) +
		cleanForSlug( slug ) +
		( permalinkSuffix + '' );

	return (
		<Modal
			title={ __( 'Edit product link', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-product-link-edit-modal"
		>
			<div className="woocommerce-product-link-edit-modal__wrapper">
				<TextControl
					label={ newProductLinkLabel }
					name="slug"
					value={ slug }
					onChange={ setSlug }
				/>
				<Text size={ 12 }>
					{ __(
						"Use simple, descriptive words and numbers. We'll replace spaces with hyphens (-).",
						'woocommerce'
					) }
				</Text>
				<div className="woocommerce-product-link-edit-modal__buttons">
					<Button isSecondary onClick={ () => onCancel() }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isPrimary
						isBusy={ isUpdatingDraft || isUpdatingPublished }
						disabled={
							isUpdatingDraft ||
							isUpdatingPublished ||
							slug === product.slug
						}
						onClick={ () => {
							onSave();
						} }
					>
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
