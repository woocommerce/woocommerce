/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

type HeaderProps = {
	product?: Product;
	title: string;
};

export function Header( { product, title }: HeaderProps ) {
	const { saveEditedEntityRecord } = useDispatch( 'core' );
	const handleSave = () =>
		saveEditedEntityRecord( 'postType', 'product', product?.id );

	return (
		<div
			className="woocommerce-product-header"
			role="region"
			aria-label={ __( 'Product Editor top bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<h1 className="woocommerce-product-header__title">{ title }</h1>
			<div className="woocommerce-product-header__actions">
				{/* @ts-ignore */}
				<Button onClick={ handleSave } variant="primary">
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
}
