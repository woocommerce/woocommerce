/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { _x, isRTL } from '@wordpress/i18n';
import { formatLtr } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { RTLToolbarButtonProps } from './types';

export function RTLToolbarButton( {
	direction,
	onChange,
}: RTLToolbarButtonProps ) {
	if ( ! isRTL() ) {
		return null;
	}

	return (
		<ToolbarButton
			icon={ formatLtr }
			title={ _x( 'Left to right', 'editor button', 'woocommerce' ) }
			isActive={ direction === 'ltr' }
			onClick={ () =>
				onChange?.( direction === 'ltr' ? undefined : 'ltr' )
			}
		/>
	);
}
