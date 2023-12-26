/**
 * External dependencies
 */
import { useParams } from 'react-router-dom';
import { MenuGroup, MenuItem } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	ProductVariation,
} from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import {
	RemoveConfirmationModal,
	__experimentalUseVariationSwitcher as useVariationSwitcher,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId, useEntityProp } from '@wordpress/core-data';

export type DeleteVariationMenuItemProps = { onClose(): void };

export const DeleteVariationMenuItem = ( {
	onClose,
}: DeleteVariationMenuItemProps ) => {
	const [ showModal, setShowModal ] = useState( false );

	const { productId } = useParams();

	const variationId = useEntityId( 'postType', 'product_variation' );

	const {
		invalidateVariationList,
		goToNextVariation,
		goToPreviousVariation,
		numberOfVariations,
	} = useVariationSwitcher( {
		parentId: productId ? parseInt( productId, 10 ) : undefined,
		variationId,
	} );

	const [ name ] = useEntityProp< string >(
		'postType',
		'product_variation',
		'name'
	);

	const [ status ] = useEntityProp< string >(
		'postType',
		'product_variation',
		'status'
	);

	const { deleteProductVariation } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	function handleMenuItemClick() {
		recordEvent( 'product_dropdown_option_click', {
			selected_option: 'delete_variation',
			product_id: productId,
			variation_id: variationId,
			product_status: status,
		} );

		setShowModal( true );
	}

	async function handleRemove() {
		recordEvent( 'product_delete_variation_modal', {
			action: 'delete',
			product_id: productId,
			variation_id: variationId,
			product_status: status,
		} );

		return deleteProductVariation< Promise< ProductVariation > >( {
			product_id: productId,
			id: variationId,
		} )
			.then( () => {
				createSuccessNotice(
					sprintf(
						// translators: %s is the variation name.
						__( '%s deleted.', 'woocommerce' ),
						name
					)
				);
				setShowModal( false );
				onClose();

				invalidateVariationList();
				if ( numberOfVariations && numberOfVariations > 1 ) {
					if ( ! goToNextVariation() ) {
						// This would only happen when deleting the last variation.
						goToPreviousVariation();
					}
				} else {
					navigateTo( {
						url: getNewPath( {}, `/product/${ productId }` ),
					} );
				}
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to delete the variation.', 'woocommerce' )
				);
			} );
	}

	function handleClose() {
		recordEvent( 'product_delete_variation_modal', {
			action: 'close',
			product_id: productId,
			variation_id: variationId,
			product_status: status,
		} );

		setShowModal( false );
	}

	return (
		<>
			<MenuGroup>
				<MenuItem
					isDestructive
					variant="tertiary"
					onClick={ handleMenuItemClick }
				>
					{ __( 'Delete variation', 'woocommerce' ) }
				</MenuItem>
			</MenuGroup>

			{ showModal && (
				<RemoveConfirmationModal
					title={ sprintf(
						// translators: %s is the variation name.
						__( 'Delete %s?', 'woocommerce' ),
						name
					) }
					description={
						<>
							<p>
								{ __(
									'If you continue, this variation with all of its information will be deleted and customers will no longer be able to purchase it.',
									'woocommerce'
								) }
							</p>

							<strong>
								{ __(
									'Deleted variations cannot be restored.',
									'woocommerce'
								) }
							</strong>
						</>
					}
					onRemove={ handleRemove }
					onCancel={ handleClose }
				/>
			) }
		</>
	);
};
