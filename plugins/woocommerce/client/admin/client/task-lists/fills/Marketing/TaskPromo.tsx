/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { useEffect } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './TaskPromo.scss';
import { WC_ASSET_URL } from '~/utils/admin-settings';

export type TaskPromoProps = {
	title?: string;
	iconSrc?: string;
	iconAlt?: string;
	name?: string;
	text?: string;
	buttonHref?: string;
	buttonText?: string;
	onButtonClick?: () => void;
};

export const TaskPromo: React.FC< TaskPromoProps > = ( {
	title = '',
	iconSrc = `${ WC_ASSET_URL }images/woo-app-icon.svg`,
	iconAlt = __( 'Woo icon', 'woocommerce' ),
	name = __( 'WooCommerce Marketplace', 'woocommerce' ),
	text = '',
	buttonHref = '',
	buttonText = '',
	onButtonClick,
} ) => {
	useEffect( () => {
		recordEvent( 'task_marketing_marketplace_promo_shown', {
			task: 'marketing',
		} );
	}, [] );

	return (
		<Card className="woocommerce-task-card woocommerce-task-promo">
			{ title && (
				<CardHeader>
					<Text
						variant="title.small"
						as="h2"
						className="woocommerce-task-card__title"
					>
						{ title }
					</Text>
				</CardHeader>
			) }
			<CardBody>
				{ iconSrc && iconAlt && (
					<div className="woocommerce-plugin-list__plugin-logo">
						<img src={ iconSrc } alt={ iconAlt } />
					</div>
				) }
				<div className="woocommerce-plugin-list__plugin-text">
					<Text variant="subtitle.small" as="h4">
						{ name }
					</Text>
					<Text variant="subtitle.small">{ text }</Text>
				</div>
				<div className="woocommerce-plugin-list__plugin-action">
					<Button
						isSecondary
						href={ buttonHref }
						onClick={ onButtonClick }
					>
						{ buttonText }
					</Button>
				</div>
			</CardBody>
		</Card>
	);
};
