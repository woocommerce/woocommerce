/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { navigateTo, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { Product } from '~/marketplace/components/product-list/types';
import { getAdminSetting } from '~/utils/admin-settings';

function ProductCardFooter( props: { product: Product } ) {
	const { product } = props;
	const [ isInstalled, setIsInstalled ] = useState( false );

	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );
		const installedProducts: string[] = wccomSettings?.installedProducts;

		const result = !! installedProducts.find(
			( item ) => item === product.slug
		);

		setIsInstalled( result );
	}, [ product ] );

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

	// We hardcode this for now while we only display prices in USD.
	const currencySymbol = '$';

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
			<div className="woocommerce-marketplace__product-card__price">
				<span className="woocommerce-marketplace__product-card__price-label">
					{
						// '0' is a free product
						product.price === 0
							? __( 'Free download', 'woocommerce' )
							: currencySymbol + product.price
					}
				</span>
				<span className="woocommerce-marketplace__product-card__price-billing">
					{ product.price === 0
						? ''
						: __( ' annually', 'woocommerce' ) }
				</span>
			</div>
			<div className="woocommerce-marketplace__product-card__rating">
				{ product.averageRating !== null && (
					<>
						<span className="woocommerce-marketplace__product-card__rating-icon">
							<Icon icon={ 'star-filled' } size={ 16 } />
						</span>
						<span className="woocommerce-marketplace__product-card__rating-average">
							{ product.averageRating }
						</span>
						<span className="woocommerce-marketplace__product-card__rating-count">
							({ product.reviewsCount })
						</span>
					</>
				) }
			</div>
		</>
	);
}

export default ProductCardFooter;
