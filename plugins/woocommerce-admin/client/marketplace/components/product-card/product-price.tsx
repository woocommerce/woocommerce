/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { InstallFlowContext } from '~/marketplace/contexts/install-flow-context';
import { Product } from '../product-list/types';

export default function ProductPrice( props: { product: Product } ) {
	const { product } = props;

	// We hardcode this for now while we only display prices in USD.
	const currencySymbol = '$';

	const { setProduct } = useContext( InstallFlowContext );

	function openInstallModal() {
		navigateTo( {
			url: getNewPath( { install: 'new' } ),
		} );

		setProduct( props.product );
	}

	if ( product.isInstallable ) {
		return (
			<>
				<span className="woocommerce-marketplace__product-card__add-to-store">
					<Button variant="secondary" onClick={ openInstallModal }>
						{ __( 'Add to Store', 'woocommerce' ) }
					</Button>
				</span>
			</>
		);
	}

	return (
		<>
			<span className="woocommerce-marketplace__product-card__price-label">
				{
					// '0' is a free product
					product.price === 0
						? __( 'Free download', 'woocommerce' )
						: currencySymbol + product.price
				}
			</span>
			<span className="woocommerce-marketplace__product-card__price-billing">
				{ product.price === 0 ? '' : __( ' annually', 'woocommerce' ) }
			</span>
		</>
	);
}
