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

export type Options = {
	[ key: string ]: unknown;
};

export type OptionsState = {
	isUpdating: boolean;
	requestingErrors:
		| {
				[ name: string ]: unknown;
		  }
		| Record< string, never >;
	error?: unknown;
	updatingError?: unknown;
} & Options;
