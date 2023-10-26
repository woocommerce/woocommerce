/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
import { search, Icon } from '@wordpress/icons';
import { useEffect } from 'react';

export function BlockInspectorMenuItem( { onClick }: { onClick: () => void } ) {
	return (
		<MenuItem
			icon={ <Icon icon={ search } /> }
			iconPosition="right"
			onClick={ onClick }
		>
			{ __( 'Show block inspector', 'woocommerce' ) }
		</MenuItem>
	);
}
