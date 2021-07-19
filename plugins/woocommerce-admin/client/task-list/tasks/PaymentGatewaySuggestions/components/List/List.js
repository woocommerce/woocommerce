/**
 * External dependencies
 */
import { Card, CardHeader } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Item } from './Item';

import './List.scss';

export const List = ( {
	heading,
	markConfigured,
	recommendation,
	paymentGateways,
} ) => {
	return (
		<Card>
			<CardHeader as="h2">{ heading }</CardHeader>
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
		</Card>
	);
};
