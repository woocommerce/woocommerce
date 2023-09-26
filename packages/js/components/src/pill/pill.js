/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { Text } from '../experimental';

export function Pill( { children, className = '' } ) {
	return (
		<Text
			className={ classnames( 'woocommerce-pill', className ) }
			variant="caption"
			as="span"
			size="12"
			lineHeight="16px"
		>
			{ children }
		</Text>
	);
}
