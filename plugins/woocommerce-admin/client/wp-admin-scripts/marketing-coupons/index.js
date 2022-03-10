/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CouponsOverview from '../../marketing/coupons';

const postForm = document.getElementById( 'posts-filter' );

if ( postForm ) {
	const couponRoot = document.createElement( 'div' );
	couponRoot.setAttribute( 'id', 'coupon-root' );

	render(
		<CouponsOverview />,
		postForm.parentNode.appendChild( couponRoot )
	);
}
