/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Product } from '../product-list/types';
import { MarketplaceContext } from '../../contexts/marketplace-context';

function ProductCardFooter( props: { product: Product } ) {
	const { product } = props;
	const { user, currentUserCan } = useUser();
	const { selectedTab, isProductInstalled } =
		useContext( MarketplaceContext );

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

	function shouldShowAddToStore( productToCheck: Product ) {
		if ( ! user || ! productToCheck ) {
			return false;
		}

		if ( ! currentUserCan( 'install_plugins' ) ) {
			return false;
		}

		// This value is sent from the WooCommerce.com API.
		if ( ! productToCheck.isInstallable ) {
			return false;
		}

		if ( productToCheck.type === 'theme' ) {
			return false;
		}

		if ( selectedTab === 'discover' ) {
			return false;
		}

		if (
			productToCheck.slug &&
			isProductInstalled( productToCheck.slug )
		) {
			return false;
		}

		return true;
	}

	const currencyFormats: { [ key: string ]: string } = {
		USD: '$%s',
		AUD: 'A$%s',
		CAD: 'C$%s',
		EUR: '€%s',
		GBP: '£%s',
	};

	const getCurrencyFormat = ( currencyCode: string ) => {
		return currencyFormats[ currencyCode ] || '%s';
	};

	function getPriceLabel(): string {
		if ( product.price === 0 ) {
			return __( 'Free download', 'woocommerce' );
		}

		if ( product.freemium_type === 'primary' ) {
			return __( 'Free plan available', 'woocommerce' );
		}

		return sprintf( getCurrencyFormat( product.currency ), product.price );
	}

	function getPriceSuffix(): string {
		// Paid simple products have a billing period of ''.
		if (
			product.billingPeriodInterval === 1 ||
			product.billingPeriod === ''
		) {
			switch ( product.billingPeriod ) {
				case 'day':
					return __( 'daily', 'woocommerce' );
				case 'week':
					return __( 'weekly', 'woocommerce' );
				case 'month':
					return __( 'monthly', 'woocommerce' );
				case 'year':
				case '':
					return __( 'annually', 'woocommerce' );
				default:
					return '';
			}
		}

		let period;
		switch ( product.billingPeriod ) {
			case 'day':
				period = __( 'days', 'woocommerce' );
				break;
			case 'week':
				period = __( 'weeks', 'woocommerce' );
				break;
			case 'month':
				period = __( 'months', 'woocommerce' );
				break;
			default:
				period = __( 'years', 'woocommerce' );
		}

		return sprintf(
			// translators: %1$d: billing period interval, %2$s: billing period (e.g. days, weeks, months, years)
			__( 'every %1$d %2$s', 'woocommerce' ),
			product.billingPeriodInterval,
			period
		);
	}

	function getBillingText(): string {
		if ( product.freemium_type === 'primary' ) {
			return '';
		}

		if ( product.price !== 0 ) {
			return getPriceSuffix();
		}

		return '';
	}

	if ( shouldShowAddToStore( product ) ) {
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
					{ getPriceLabel() }
				</span>
				<span className="woocommerce-marketplace__product-card__price-billing">
					{ getBillingText() }
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
