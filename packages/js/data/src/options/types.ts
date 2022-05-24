/**
 * Internal dependencies
 */
import { WPDataSelector, WPDataSelectors } from '../types';
import {
	getOptionsRequestingError,
	isOptionsUpdating,
	getOptionsUpdatingError,
} from './selectors';

export type OptionsSelectors = {
	getOption: < T = string >( option: string ) => T;
	// getOption: getOption;
	getOptionsRequestingError: WPDataSelector<
		typeof getOptionsRequestingError
	>;
	isOptionsUpdating: WPDataSelector< typeof isOptionsUpdating >;
	getOptionsUpdatingError: WPDataSelector< typeof getOptionsUpdatingError >;
} & WPDataSelectors;
