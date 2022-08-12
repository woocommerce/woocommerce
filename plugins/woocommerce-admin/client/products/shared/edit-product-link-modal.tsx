/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { useProductHelper } from '../use-product-helper';

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
	const { updateProductWithStatus, isUpdatingDraft, isUpdatingPublished } =
		useProductHelper();
	const [ slug, setSlug ] = useState( product.slug );

	const onSave = async () => {
		await updateProductWithStatus(
			product.id,
			{
				slug,
			},
			product.status
		);
		onSaved();
	};

	const newProductLinkLabel = product.permalink.replace( product.slug, slug );

	return (
		<Modal
			title={ __( 'Edit product link', 'woocommerce' ) }
			onRequestClose={ () => {} }
			className="woocommerce-inbox-dismiss-confirmation_modal"
		>
			<div className="woocommerce-inbox-dismiss-confirmation_wrapper">
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
				<div className="woocommerce-inbox-dismiss-confirmation_buttons">
					<Button isSecondary onClick={ () => onCancel() }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isSecondary
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
