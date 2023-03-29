/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
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

	const isDisabled = isSavingLocked || isSaving;
	const isCreating = ( productStatus as string ) === 'auto-draft';

	function handleSaveSuccess( product: Product ) {
		if ( isCreating ) {
			const url = getNewPath( {}, `/product/${ product.id }` );
			navigateTo( { url } );
		}
	}

	const saveDraftButtonProps = useSaveDraft( {
		productId,
		disabled: isDisabled,
		onSaveSuccess: handleSaveSuccess,
	} );

	const previewButtonProps = usePreview( {
		productId,
		disabled: isDisabled,
		onSaveSuccess: handleSaveSuccess,
	} );

	const publishButtonProps = usePublish( {
		productId,
		disabled: isDisabled,
		isBusy: isSaving,
		onPublishSuccess: handleSaveSuccess,
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
