/** @format */
/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import CustomizableDashboard from './customizable';
import ProfileWizard from './profile-wizard';
import withSelect from 'wc-api/with-select';
import { isOnboardingEnabled } from 'dashboard/utils';

class Dashboard extends Component {
	componentDidUpdate( prevProps ) {
		const profileItems = get( this.props, 'profileItems', {} );
		const prevProfileItems = get( prevProps, 'profileItems', {} );

		if ( profileItems.completed && ! prevProfileItems.completed ) {
			updateQueryString( {}, '/', {} );
			this.redirectToCart();
		}
	}

	getProductIds() {
		const productIds = [];
		const profileItems = get( this.props, 'profileItems', {} );
		const onboarding = getSetting( 'onboarding', {} );

		profileItems.product_types.forEach( product_type => {
			if (
				onboarding.productTypes[ product_type ] &&
				onboarding.productTypes[ product_type ].product
			) {
				productIds.push( onboarding.productTypes[ product_type ].product );
			}
		} );

		const theme = onboarding.themes.find( themeData => themeData.slug === profileItems.theme );

		if ( theme && theme.id && ! theme.is_installed ) {
			productIds.push( theme.id );
		}

		return productIds;
	}

	redirectToCart() {
		if ( ! isOnboardingEnabled() ) {
			return;
		}

		const productIds = this.getProductIds();
		if ( ! productIds.length ) {
			return;
		}

		document.body.classList.add( 'woocommerce-admin-is-loading' );

		const url = addQueryArgs( 'https://woocommerce.com/cart', {
			'wccom-back': window.location.href,
			'wccom-replace-with': productIds.join( ',' ),
		} );
		window.location = url;
	}

	render() {
		const { path, profileItems, query } = this.props;

		if ( isOnboardingEnabled() && ! profileItems.completed ) {
			return <ProfileWizard query={ query } />;
		}

		if ( window.wcAdminFeatures[ 'analytics-dashboard/customizable' ] ) {
			return <CustomizableDashboard query={ query } path={ path } />;
		}

		return null;
	}
}

export default compose(
	withSelect( select => {
		if ( ! isOnboardingEnabled() ) {
			return;
		}

		const { getProfileItems } = select( 'wc-api' );
		const profileItems = getProfileItems();

		return { profileItems };
	} )
)( Dashboard );
