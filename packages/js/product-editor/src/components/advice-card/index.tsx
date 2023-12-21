/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Card, CardBody, CardFooter } from '@wordpress/components';

export interface AdviceCardProps {
	tip?: string;
	isDismissible?: boolean;
	onDismiss?: () => void;
	image?: React.ReactNode;
}

export const AdviceCard: React.FC< AdviceCardProps > = ( {
	tip,
	image = null,
} ) => {
	return (
		<Card className="woocommerce-advice-card">
			<CardBody>{ image }</CardBody>
			{ tip && tip.length > 0 && <CardFooter>{ tip }</CardFooter> }
		</Card>
	);
};
