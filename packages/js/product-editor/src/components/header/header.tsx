/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import { WooHeaderItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { getHeaderTitle } from '../../utils';
import { MoreMenu } from './more-menu';
import { usePreview } from './hooks/use-preview';
import { usePublish } from './hooks/use-publish';
import { useSaveDraft } from './hooks/use-save-draft';
import { MouseEvent } from 'react';

export type HeaderProps = {
	productId: number;
	productName: string;
	productStatus: ProductStatus;
};

export function Header( {
	productId,
	productName,
	productStatus,
}: HeaderProps ) {
	const { isSavingLocked, isSaving, editedProductName } = useSelect(
		( select ) => {
			const { isSavingEntityRecord, getEditedEntityRecord } =
				select( 'core' );
			const { isPostSavingLocked } = select( 'core/editor' );

			const product: Product = getEditedEntityRecord(
				'postType',
				'product',
				productId
			);

			return {
				isSavingLocked: isPostSavingLocked(),
				isSaving: isSavingEntityRecord(
					'postType',
					'product',
					productId
				),
				editedProductName: product?.name,
			};
		},
		[ productId ]
	);

	const { createNotice } = useDispatch( 'core/notices' );

	const isDisabled = isSavingLocked || isSaving;
	const isCreating = ( productStatus as string ) === 'auto-draft';

	function tryRedirectToEditPage( id: number ) {
		if ( isCreating ) {
			const url = getNewPath( {}, `/product/${ id }` );
			navigateTo( { url } );
		}
	}

	const saveDraftButtonProps = useSaveDraft( {
		productId,
		disabled: isDisabled,
		onSaveSuccess( savedProduct: Product ) {
			createNotice(
				'success',
				__( 'Product saved as draft.', 'woocommerce' )
			);

			tryRedirectToEditPage( savedProduct.id );
		},
		onSaveError() {
			createNotice(
				'error',
				__( 'Failed to update product.', 'woocommerce' )
			);
		},
	} );

	const previewButtonProps = usePreview( {
		productId,
		disabled: isDisabled,
		onSaveSuccess( savedProduct: Product ) {
			tryRedirectToEditPage( savedProduct.id );
		},
		onSaveError() {
			createNotice(
				'error',
				__( 'Failed to preview product.', 'woocommerce' )
			);
		},
	} );

	const publishButtonProps = usePublish( {
		productId,
		disabled: isDisabled,
		isBusy: isSaving,
		onPublishSuccess( savedProduct: Product ) {
			const noticeContent = isCreating
				? __( 'Product successfully created.', 'woocommerce' )
				: __( 'Product published.', 'woocommerce' );
			const noticeOptions = {
				icon: '🎉',
				actions: [
					{
						label: __( 'View in store', 'woocommerce' ),
						// Leave the url to support a11y
						url: savedProduct.permalink,
						onClick( event: MouseEvent ) {
							event.preventDefault();
							// Notice actions does not support target anchor prop
							// So this forces the page to be opened in a new tab
							window.open( savedProduct.permalink, '_blank' );
						},
					},
				],
			};

			createNotice( 'success', noticeContent, noticeOptions );

			tryRedirectToEditPage( savedProduct.id );
		},
		onPublishError() {
			const noticeContent = isCreating
				? __( 'Failed to create product.', 'woocommerce' )
				: __( 'Failed to publish product.', 'woocommerce' );

			createNotice( 'error', noticeContent );
		},
	} );

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<h1 className="woocommerce-product-header__title">
				{ getHeaderTitle( editedProductName, productName ) }
			</h1>

			<div className="woocommerce-product-header__actions">
				<Button { ...saveDraftButtonProps } />

				<Button { ...previewButtonProps } />

				<Button { ...publishButtonProps }>
					{ isCreating
						? __( 'Add', 'woocommerce' )
						: __( 'Save', 'woocommerce' ) }
				</Button>

				<WooHeaderItem.Slot name="product" />
				<MoreMenu />
			</div>
		</div>
	);
}
