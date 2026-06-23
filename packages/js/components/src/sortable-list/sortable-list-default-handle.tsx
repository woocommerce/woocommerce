/**
 * External dependencies
 */
import { dragHandle, Icon } from '@wordpress/icons';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	SortableListHandle,
	type SortableListHandleProps,
} from './sortable-list-handle';

export const SortableListDefaultHandle = (
	props: Omit< SortableListHandleProps, 'children' >
) => (
	<SortableListHandle { ...props }>
		<Icon icon={ dragHandle } size={ 20 } />
	</SortableListHandle>
);
