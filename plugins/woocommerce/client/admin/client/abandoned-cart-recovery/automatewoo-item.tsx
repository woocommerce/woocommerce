/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

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
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'AutomateWoo', 'woocommerce' ) }
					<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Build multi-step recovery sequences, segment customers, and track which campaigns recover the most revenue.',
						'woocommerce'
					) }
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					variant="secondary"
					href={ AUTOMATEWOO_URL }
					target="_blank"
					rel="noreferrer"
					onClick={ handleClick }
				>
					{ __( 'Learn more', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default AutomateWooItem;
