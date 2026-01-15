/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { Text } from '../experimental';

export function Pill( { children, className = '' } ) {
	return (
		<Text
			className={ clsx( 'woocommerce-pill', className ) }
			variant="caption"
			as="span"
			size="12"
			lineHeight="16px"
		>
			{ children }
		</Text>
	);
}
