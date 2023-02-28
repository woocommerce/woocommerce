/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState, createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import interpolateComponents from '@automattic/interpolate-components';
import { Product } from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { __experimentalUseProductHelper as useProductHelper } from '@woocommerce/product-editor';

type EditProductLinkModalProps = {
	product: Product;
	permalinkPrefix: string;
	permalinkSuffix: string;
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
	const { updateProductWithStatus, isUpdatingDraft, isUpdatingPublished } =
		useProductHelper();
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
		const updatedProduct = await updateProductWithStatus(
			product.id,
			{
				slug,
			},
			product.status,
			true
		);
		if ( updatedProduct && updatedProduct.id ) {
			// only reset the updated slug and permalink fields.
			resetForm(
				{
					...product,
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
		permalinkPrefix + cleanForSlug( slug ) + permalinkSuffix;

	return (
		<Modal
			title={ __( 'Edit product link', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-product-link-edit-modal"
		>
			<div className="woocommerce-product-link-edit-modal__wrapper">
				<p className="woocommerce-product-link-edit-modal__description">
					{ __(
						"Create a unique link for this product. Use simple, descriptive words and numbers. We'll replace spaces with hyphens (-).",
						'woocommerce'
					) }
				</p>
				<TextControl
					label={ __( 'Product link', 'woocommerce' ) }
					name="slug"
					value={ slug }
					onChange={ setSlug }
					hideLabelFromVision
					help={ interpolateComponents( {
						mixedString: __( 'Preview: {{link/}}', 'woocommerce' ),
						components: {
							link: <strong>{ newProductLinkLabel }</strong>,
						},
					} ) }
				/>
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
