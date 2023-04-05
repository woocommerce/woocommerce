/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import {
	useState,
	createElement,
	createInterpolateElement,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

type EditProductLinkModalProps = {
	product: Product;
	permalinkPrefix: string;
	permalinkSuffix: string;
	onCancel: () => void;
	onSaved: () => void;
	saveHandler: (
		slug: string
	) => Promise< { slug: string; permalink: string } | undefined >;
	isBusy?: boolean;
	disabled?: boolean;
};

export const EditProductLinkModal: React.FC< EditProductLinkModalProps > = ( {
	product,
	permalinkPrefix,
	permalinkSuffix,
	onCancel,
	onSaved,
	saveHandler,
	isBusy,
	disabled,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );

	const [ slug, setSlug ] = useState(
		product.slug || cleanForSlug( product.name )
	);

	const onSave = async () => {
		recordEvent( 'product_update_slug', {
			new_product_page: true,
			product_id: product.id,
			product_type: product.type,
		} );

		const updatedValues = await saveHandler( slug );

		if ( updatedValues ) {
			createNotice(
				updatedValues.slug === cleanForSlug( slug )
					? 'success'
					: 'info',
				updatedValues.slug === cleanForSlug( slug )
					? __( 'Product link successfully updated.', 'woocommerce' )
					: __(
							'Product link already existed, updated to ',
							'woocommerce'
					  ) + updatedValues.permalink
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
					help={ createInterpolateElement(
						__( 'Preview: <link />', 'woocommerce' ),
						{
							link: <strong>{ newProductLinkLabel }</strong>,
						}
					) }
				/>
				<div className="woocommerce-product-link-edit-modal__buttons">
					<Button isSecondary onClick={ () => onCancel() }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isPrimary
						isBusy={ isBusy }
						disabled={ disabled || slug === product.slug }
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
