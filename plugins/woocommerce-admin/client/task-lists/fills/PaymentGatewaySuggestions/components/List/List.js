/**
 * External dependencies
 */
import { Card, CardHeader, CardFooter } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Item } from './Item';

import './List.scss';

export const List = ( {
	heading,
	headingDescription,
	markConfigured,
	recommendation,
	paymentGateways,
	footerLink,
} ) => {
	return (
		<Card>
			{ heading && (
				<CardHeader as="h2">
					{ heading }
					{ headingDescription && (
						<p className="woocommerce-task-payment-header__description">
							{ headingDescription }
						</p>
					) }
				</CardHeader>
			) }
			{ paymentGateways.map( ( paymentGateway ) => {
				const { id } = paymentGateway;
				return (
					<Item
						key={ id }
						isRecommended={ recommendation === id }
						markConfigured={ markConfigured }
						paymentGateway={ paymentGateway }
					/>
				);
			} ) }
			{ footerLink && (
				<CardFooter isBorderless>{ footerLink }</CardFooter>
			) }
		</Card>
	);
};
