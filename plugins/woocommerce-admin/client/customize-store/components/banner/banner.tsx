/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import './banner.scss';

export const Banner = () => {
	return (
		<div className="woocommerce-customize-store-banner">
			<div className={ `woocommerce-customize-store-banner-content` }>
				<h1>{ __( 'Use the power of AI to design your store', 'woocommerce' ) }</h1>
				<p>{ __( 'Design the look of your store, create pages, and generate copy using our built-in AI tools.', 'woocommerce' ) }</p>
				<div>
					<button>{ __( 'Design with AI', 'woocommerce' ) }</button>
				</div>
			</div>
		</div>
	);
};
