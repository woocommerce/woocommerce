/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { Button } from '@wordpress/components';
// import { createInterpolateElement, useState } from '@wordpress/element';
// import { Link } from '@woocommerce/components';
// import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ADMIN_URL } from '~/utils/admin-settings';

const marketing = {
	title: __( 'Promote your products', 'woocommerce' ),
	description: __(
		'Grow your customer base by promoting your products to millions of engaged shoppers.',
		'woocommerce'
	),
	link: `${ ADMIN_URL }edit.php?post_type=product`,
	linkText: __( 'Promote products', 'woocommerce' ),
	trackEvent: 'launch_you_store_congrats_marketing_click',
};

const payments = {
	title: __( 'Provide more ways to pay', 'woocommerce' ),
	description: __(
		'Give your shoppers more ways to pay by adding additional payment methods to your store.',
		'woocommerce'
	),
	link: `${ ADMIN_URL }site-editor.php`,
	linkText: __( 'Add payment methods', 'woocommerce' ),
	trackEvent: 'launch_you_store_congrats_payments_click',
};

const extensions = {
	title: __( 'Power up your store', 'woocommerce' ),
	description: __(
		'Add extra features and functionality to your store with Woo extensions.',
		'woocommerce'
	),
	link: `${ ADMIN_URL }site-editor.php`,
	linkText: __( 'Add extensions', 'woocommerce' ),
	trackEvent: 'launch_you_store_congrats_extensions_click',
};

const getList = () => {
	return [ marketing, payments, extensions ];
};

export const WhatsNext = ( { goToHome }: { goToHome: () => void } ) => {
	return (
		<div className="woocommerce-launch-store__congrats-main-actions">
			{ getList().map( ( item, index ) => (
				<div
					className="woocommerce-launch-store__congrats-action"
					key={ index }
				>
					<div className="woocommerce-launch-store__congrats-action__content">
						<h3>{ item.title }</h3>
						<p>{ item.description }</p>
						<Button
							variant="link"
							onClick={ () => {
								recordEvent( item.trackEvent );
								location.href = item.link;
							} }
						>
							{ item.linkText }
						</Button>
					</div>
				</div>
			) ) }
		</div>
	);
};
