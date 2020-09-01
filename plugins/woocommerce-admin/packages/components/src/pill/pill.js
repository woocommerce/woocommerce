/**
 * External dependencies
 */
import { __experimentalText as Text } from '@wordpress/components';
export function Pill( { children } ) {
	return (
		<Text className="woocommerce-pill" variant="caption" as="span">
			{ children }
		</Text>
	);
}
