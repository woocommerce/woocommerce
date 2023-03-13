/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';

export type HeaderProps = {
	product: Product;
	title: string;
};

export function Header( { product, title }: HeaderProps ) {
	const { isProductLocked, isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord } = select( 'core' );
			const { isPostSavingLocked } = select( 'core/editor' );
			return {
				isProductLocked: isPostSavingLocked(),
				isSaving: isSavingEntityRecord(
					'postType',
					'product',
					product.id
				),
			};
		},
		[ product.id ]
	);

	const isDisabled = isProductLocked || isSaving;

	const { saveEditedEntityRecord } = useDispatch( 'core' );

	function handleSave() {
		saveEditedEntityRecord( 'postType', 'product', product.id );
	}

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<h1 className="woocommerce-product-header__title">{ title }</h1>

			<div className="woocommerce-product-header__actions">
				<Button
					onClick={ handleSave }
					variant="primary"
					isBusy={ isSaving }
					disabled={ isDisabled }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
}
