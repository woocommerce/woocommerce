/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import { WooHeaderItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { AUTO_DRAFT_NAME, getHeaderTitle } from '../../utils';
import { MoreMenu } from './more-menu';
import { usePreview } from './hooks/use-preview';
import { usePublish } from './hooks/use-publish';

export type HeaderProps = {
	productId: number;
	productName: string;
};

export function Header( { productId, productName }: HeaderProps ) {
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
	const isCreating = productName === AUTO_DRAFT_NAME;

	const previewButtonProps = usePreview( {
		productId,
		disabled: isDisabled,
		'aria-label': __( 'Preview in new tab', 'woocommerce' ),
	} );

	const publishButtonProps = usePublish( {
		productId,
		disabled: isDisabled,
		isBusy: isSaving,
		onPublishSuccess( product ) {
			if ( isCreating ) {
				const url = getNewPath( {}, `/product/${ product.id }` );
				navigateTo( { url } );
			}
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
				<Button { ...previewButtonProps }>
					{ __( 'Preview', 'woocommerce' ) }
				</Button>

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
