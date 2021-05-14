/**
 * Internal dependencies
 */
import { Text } from '../experimental';

export function Pill( { children } ) {
	return (
		<Text className="woocommerce-pill" variant="caption" as="span">
			{ children }
		</Text>
	);
}
