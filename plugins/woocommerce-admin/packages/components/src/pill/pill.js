/**
 * External dependencies
 */
import { Text } from '@woocommerce/experimental';

export function Pill( { children } ) {
	return (
		<Text className="woocommerce-pill" variant="caption" as="span">
			{ children }
		</Text>
	);
}
