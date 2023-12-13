/**
 * External dependencies
 */
import { FormEvent } from 'react';
import { Button, Modal } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall, dragHandle } from '@wordpress/icons';
import { Product } from '@woocommerce/data';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { AddProductsModalProps } from './types';
import { useDraggable } from '../../hooks/use-draggable';
import { getProductImageStyle } from '../add-products-modal';

export function ReorderProductsModal( {
	products,
	onSubmit,
	onClose,
}: AddProductsModalProps ) {
	const [ selectedProducts, setSelectedProducts ] = useState< Product[] >( [
		...products,
	] );

	function handleSubmit( event: FormEvent< HTMLFormElement > ) {
		event.preventDefault();

		onSubmit( [ ...selectedProducts ] );
	}

	function handleCancelClick() {
		onClose();
	}

	function removeProductHandler( product: Product ) {
		return function handleRemoveClick() {
			setSelectedProducts( ( current ) =>
				current.filter( ( item ) => item.id !== product.id )
			);
		};
	}

	const { container, draggable, handler } = useDraggable( {
		onSort: setSelectedProducts,
	} );

	return (
		<Modal
			title={ __( 'Reorder products in this group', 'woocommerce' ) }
			className="woocommerce-reorder-products-modal"
			onRequestClose={ onClose }
		>
			<form
				noValidate
				onSubmit={ handleSubmit }
				className="woocommerce-add-products-modal__form"
			>
				<fieldset className="woocommerce-add-products-modal__form-group">
					<legend className="woocommerce-add-products-modal__form-group-title">
						{ __(
							'Click and drag to reorder on the product page.',
							'woocommerce'
						) }
					</legend>

					{ Boolean( selectedProducts.length ) && (
						<ul
							{ ...container }
							className={ classNames(
								'woocommerce-add-products-modal__list',
								container.className
							) }
						>
							{ selectedProducts.map( ( item ) => (
								<li
									{ ...draggable }
									key={ item.id }
									className="woocommerce-add-products-modal__list-item"
								>
									<Button
										{ ...handler }
										icon={ dragHandle }
										variant="tertiary"
										type="button"
										aria-label={ __(
											'Sortable handler',
											'woocommerce'
										) }
									/>
									<div
										className="woocommerce-add-products-modal__list-item-image"
										style={ getProductImageStyle( item ) }
									/>
									<div className="woocommerce-add-products-modal__list-item-content">
										<div className="woocommerce-add-products-modal__list-item-title">
											{ item.name }
										</div>

										<div className="woocommerce-add-products-modal__list-item-description">
											{ item.sku }
										</div>
									</div>

									<div className="woocommerce-add-products-modal__list-item-actions">
										<Button
											type="button"
											variant="tertiary"
											icon={ closeSmall }
											aria-label={ __(
												'Remove product',
												'woocommerce'
											) }
											onClick={ removeProductHandler(
												item
											) }
										/>
									</div>
								</li>
							) ) }
						</ul>
					) }
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
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</form>
		</Modal>
	);
}
