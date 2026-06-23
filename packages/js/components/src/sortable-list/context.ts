/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { SortableListHandleContextType } from './types';

export const SortableListContext = createContext< {
	disabledIds: Set< string >;
} >( {
	disabledIds: new Set(),
} );

export const SortableListHandleContext =
	createContext< SortableListHandleContextType >( {
		attributes: null,
		disabled: true,
		listeners: null,
		setActivatorNodeRef: () => undefined,
	} );
