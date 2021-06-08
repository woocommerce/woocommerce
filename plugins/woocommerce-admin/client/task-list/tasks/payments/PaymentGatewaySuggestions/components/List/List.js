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
	paymentGateways,
	recommendation,
	suggestions,
} ) => {
	return (
		<Card>
			<CardHeader as="h2">{ heading }</CardHeader>
			{ Array.from( suggestions.values() ).map( ( suggestion ) => {
				const { id } = suggestion;
				return (
					<Item
						key={ id }
						isRecommended={ recommendation === id }
						markConfigured={ markConfigured }
						paymentGateways={ paymentGateways }
						suggestion={ suggestion }
						suggestionKeys={ suggestions.keys() }
					/>
				);
			} ) }
		</Card>
	);
};
