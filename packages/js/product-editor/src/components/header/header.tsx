/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { AUTO_DRAFT_NAME } from '../../utils';

export type HeaderProps = {
	productId: number;
	productName: string;
};

export function Header( { productId, productName }: HeaderProps ) {
	const { isProductLocked, isSaving, product } = useSelect(
		( select ) => {
			const { isSavingEntityRecord, getEditedEntityRecord } =
				select( 'core' );
			const { isPostSavingLocked } = select( 'core/editor' );
			return {
				isProductLocked: isPostSavingLocked(),
				isSaving: isSavingEntityRecord(
					'postType',
					'product',
					productId
				),
				product: getEditedEntityRecord(
					'postType',
					'product',
					productId
				) as Product,
			};
		},
		[ productId ]
	);

	const isDisabled = isProductLocked || isSaving;
	const isProductNameNotEmpty = Boolean( product?.name );
	const isProductNameDirty = product.name !== productName;
	const isCreating = productName === AUTO_DRAFT_NAME;

	let title = '';
	if ( isProductNameNotEmpty && isProductNameDirty ) {
		title = product.name;
	} else if ( isCreating ) {
		title = __( 'Add new product', 'woocommerce' );
	} else {
		title = productName;
	}

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
			<h1 className="woocommerce-product-header__title">{ title }</h1>

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
