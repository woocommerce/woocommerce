/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export type HeaderProps = {
	productId: number;
	title: string;
};

const DEFAULT_PRODUCT_NAME = 'AUTO-DRAFT';

export function Header( { productId, title }: HeaderProps ) {
	const { isProductLocked, isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord } = select( 'core' );
			const { isPostSavingLocked } = select( 'core/editor' );
			return {
				isProductLocked: isPostSavingLocked(),
				isSaving: isSavingEntityRecord(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const isDisabled = isProductLocked || isSaving;
	const isCreating = title === DEFAULT_PRODUCT_NAME;

	const { saveEditedEntityRecord } = useDispatch( 'core' );

	function handleSave() {
		saveEditedEntityRecord( 'postType', 'product', productId );
	}

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<h1 className="woocommerce-product-header__title">
				{ isCreating ? __( 'Add new product', 'woocommerce' ) : title }
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
			</div>
		</div>
	);
}
