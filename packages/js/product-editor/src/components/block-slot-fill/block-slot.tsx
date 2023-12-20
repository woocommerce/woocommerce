/**
 * External dependencies
 */
import { Slot } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getName } from './utils/get-name';
import { BlockSlotProps } from './types';

export function BlockSlot( { name, clientId, ...props }: BlockSlotProps ) {
	return <Slot { ...props } name={ getName( name, clientId ) } />;
}
