/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

const MAILPOET_URL =
	'https://woocommerce.com/products/mailpoet/?utm_source=woocommerce&utm_medium=product&utm_campaign=abandoned-cart-recovery-recommendation';

const MailPoetItem = () => {
	const handleClick = () => {
		recordEvent( 'abandoned_cart_recovery_recommendation_click', {
			plugin: 'mailpoet',
		} );
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-abandoned-cart-recovery-recommendation-item">
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'MailPoet', 'woocommerce' ) }
					<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Pair recovery emails with newsletters and ongoing automations so customers stay engaged after they buy.',
						'woocommerce'
					) }
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					variant="secondary"
					href={ MAILPOET_URL }
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

export default MailPoetItem;
