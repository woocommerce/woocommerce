/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { _x, isRTL } from '@wordpress/i18n';
import { formatLtr } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { ParagraphRTLControlProps } from './types';

export function ParagraphRTLControl( {
	direction,
	onChange,
}: ParagraphRTLControlProps ) {
	function handleClick() {
		if ( typeof onChange === 'function' ) {
			onChange( direction === 'ltr' ? undefined : 'ltr' );
		}
	}

	return (
		<>
			{ isRTL() && (
				<ToolbarButton
					icon={ formatLtr }
					title={ _x(
						'Left to right',
						'editor button',
						'woocommerce'
					) }
					isActive={ direction === 'ltr' }
					onClick={ handleClick }
				/>
			) }
		</>
	);
}
