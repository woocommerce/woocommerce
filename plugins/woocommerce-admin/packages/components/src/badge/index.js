/**
 * External dependencies
 */
import React from '@wordpress/element';

export const Badge = ( { count, className = '', ...props } ) => {
	return (
		<span className={ `woocommerce-badge ${ className }` } { ...props }>
			{ count }
		</span>
	);
};
