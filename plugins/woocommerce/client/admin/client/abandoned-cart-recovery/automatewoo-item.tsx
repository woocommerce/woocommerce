/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ProductIcon } from '~/marketing/components';

const AUTOMATEWOO_URL =
	'https://woocommerce.com/products/automatewoo/?utm_source=woocommerce&utm_medium=product&utm_campaign=abandoned-cart-recovery-recommendation';

const AutomateWooItem = () => {
	const handleClick = () => {
		recordEvent( 'abandoned_cart_recovery_recommendation_click', {
			plugin: 'automatewoo',
		} );
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-abandoned-cart-recovery-recommendation-item">
			<div className="woocommerce-list__item-before">
				<ProductIcon product="automatewoo" />
			</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'AutomateWoo', 'woocommerce' ) }
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Set up multi-step abandoned cart sequences, win-back flows, and review requests. Track exactly which campaigns earn the most revenue.',
						'woocommerce'
					) }
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					variant="secondary"
					href={ AUTOMATEWOO_URL }
					target="_blank"
					rel="noopener noreferrer"
					onClick={ handleClick }
				>
					{ __( 'Learn more', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default AutomateWooItem;
