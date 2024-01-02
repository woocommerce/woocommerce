/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
} from '@wordpress/components';
import { close } from '@wordpress/icons';

export interface AdviceCardProps {
	tip?: string;
	isDismissible?: boolean;
	onDismiss?: () => void;
	image?: React.ReactNode;
}

export const AdviceCard: React.FC< AdviceCardProps > = ( {
	tip,
	image = null,
	isDismissible = false,
	onDismiss = () => {},
} ) => {
	return (
		<Card className="woocommerce-advice-card">
			{ isDismissible && (
				<CardHeader>
					<Button
						className="woocommerce-advice-card__dismiss-button"
						onClick={ onDismiss }
						icon={ close }
						label={ __( 'Dismiss', 'woocommerce' ) }
					/>
				</CardHeader>
			) }
			<CardBody>{ image }</CardBody>
			{ tip && tip.length > 0 && <CardFooter>{ tip }</CardFooter> }
		</Card>
	);
};
