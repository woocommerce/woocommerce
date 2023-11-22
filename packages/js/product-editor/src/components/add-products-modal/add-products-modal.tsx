/**
 * External dependencies
 */
import { FormEvent } from 'react';
import { Button, Modal } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AddProductsModalProps } from './types';

export function AddProductsModal( {
	onSubmit,
	onClose,
}: AddProductsModalProps ) {
	const [ value, setValue ] = useState< Product[] >( [] );

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		onSubmit( [ ...value ] );
	}

	function handleCancelClick() {
		onClose();
	}

	return (
		<Modal
			title={ __( 'Add products to this group', 'woocommerce' ) }
			className="woocommerce-add-products-modal"
			onRequestClose={ onClose }
		>
			<form noValidate onSubmit={ handleSubmit }>
				<fieldset>
					<legend>
						{ __(
							'Add and manage products in this group to let customers purchase them all in one go.',
							'woocommerce'
						) }
					</legend>

					<div className="woocommerce-add-products-modal__content"></div>
				</fieldset>

				<div className="woocommerce-add-products-modal__actions">
					<Button
						variant="tertiary"
						type="button"
						onClick={ handleCancelClick }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button variant="primary" type="submit">
						{ __( 'Add', 'woocommerce' ) }
					</Button>
				</div>
			</form>
		</Modal>
	);
}
