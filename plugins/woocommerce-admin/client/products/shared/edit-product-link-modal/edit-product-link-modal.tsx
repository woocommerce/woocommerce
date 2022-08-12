/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
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
	onCancel: () => void;
	onSaved: () => void;
};

export const EditProductLinkModal: React.FC< EditProductLinkModalProps > = ( {
	product,
	onCancel,
	onSaved,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateProductWithStatus, isUpdatingDraft, isUpdatingPublished } =
		useProductHelper();
	const [ slug, setSlug ] = useState( product.slug );
	const { resetForm, changedFields, touched, errors } =
		useFormContext< Product >();

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
				changedFields,
				touched,
				errors
			);
			createNotice(
				updatedProduct.slug ===
					slug.toLowerCase().replaceAll( ' ', '-' )
					? 'success'
					: 'info',
				updatedProduct.slug ===
					slug.toLowerCase().replaceAll( ' ', '-' )
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

	const newProductLinkLabel = product.permalink.replace(
		new RegExp( `(.*)${ product.slug }(.*)` ),
		`$1${ slug.toLowerCase().replaceAll( ' ', '-' ) }$2`
	);

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
