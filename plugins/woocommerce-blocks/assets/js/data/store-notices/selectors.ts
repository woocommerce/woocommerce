/**
 * Internal dependencies
 */
import { StoreNoticesState } from './default-state';

export const getContainers = (
	state: StoreNoticesState
): StoreNoticesState[ 'containers' ] => state.containers;
