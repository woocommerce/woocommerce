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

/**
 * Internal dependencies
 */
import { MoreMenu } from './more-menu';

export type HeaderProps = {
	productId: number;
	productName: string;
};

export function Header( { productId, productName }: HeaderProps ) {
	const { isProductLocked, isSaving, editedProductName } = useSelect(
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
				isProductLocked: isPostSavingLocked(),
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

	const isDisabled = isProductLocked || isSaving;
	const isCreating = productName === AUTO_DRAFT_NAME;

	const { saveEditedEntityRecord } = useDispatch( 'core' );

	function handleSave() {
		saveEditedEntityRecord< Product >(
			'postType',
			'product',
			productId
		).then( ( response ) => {
			if ( isCreating ) {
				navigateTo( {
					url: getNewPath( {}, `/product/${ response.id }` ),
				} );
			}
		} );
	}

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
				<Button
					onClick={ handleSave }
					variant="primary"
					isBusy={ isSaving }
					disabled={ isDisabled }
				>
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
