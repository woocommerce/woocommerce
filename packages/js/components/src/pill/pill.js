/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Text } from '../experimental';

export function Pill( { children } ) {
	return (
		<Text
			className="woocommerce-pill"
			variant="caption"
			as="span"
			size="12"
			lineHeight="16px"
		>
			{ children }
		</Text>
	);
}
