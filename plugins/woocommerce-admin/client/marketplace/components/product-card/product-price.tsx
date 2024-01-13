/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Product } from '../product-list/types';
import { getAdminSetting } from '~/utils/admin-settings';

export default function ProductPrice( props: { product: Product } ) {
	const { product } = props;

	// We hardcode this for now while we only display prices in USD.
	const currencySymbol = '$';

	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	const installedProducts: string[] = wccomSettings?.installedProducts;

	const isInstalled = !! installedProducts.find(
		( item ) => item === product.slug
	);

	function openInstallModal() {
		recordEvent( 'marketplace_add_to_store_clicked', {
			product_id: product.id,
		} );

		navigateTo( {
			url: getNewPath( {
				installProduct: product.id,
			} ),
		} );
	}

	if ( product.isInstallable && ! isInstalled ) {
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
