/**
 * External dependencies
 */
import { Slot } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { useBlockEditContext } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { getName } from './utils/get-name';
import { BlockSlotProps } from './types';

export function BlockSlot( { name, ...props }: BlockSlotProps ) {
	const { clientId } = useBlockEditContext();
	return <Slot { ...props } name={ getName( name, clientId ) } />;
}
