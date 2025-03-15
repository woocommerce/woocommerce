/**
 * External dependencies
 */

import { createReduxStore, register } from '@wordpress/data';
import { SelectFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import controls from '../controls';
import reducer, { State } from './reducer';
import { WPDataSelectors } from '../types';
import {
	ReportItemObjectInfer,
	ReportItemsEndpoint,
	ReportQueryParams,
	ReportStatEndpoint,
	ReportStatObjectInfer,
	ReportStatQueryParams,
} from './types';
import { PromiseifySelectors } from '../types/promiseify-selectors';
export * from './types';
export type { State };

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	controls,
	selectors,
	resolvers,
} );

register( store );

export const REPORTS_STORE_NAME = STORE_NAME;

// We need to provide those types to support type parameters in the selectors.
export type ReportsSelect = WPDataSelectors &
	Omit<
		// SelectFromMap removes type parameters, so we need to redo it ourselves.
		SelectFromMap< typeof selectors >,
		'getReportItems' | 'getReportStats'
	> & {
		getReportItems: < T >(
			endpoint: ReportItemsEndpoint,
			query: ReportQueryParams
		) => ReportItemObjectInfer< T >;
		getReportStats: < T >(
			endpoint: ReportStatEndpoint,
			query: ReportStatQueryParams
		) => ReportStatObjectInfer< T >;
	};

declare module '@wordpress/data' {
	function select( key: typeof STORE_NAME | typeof store ): ReportsSelect;
	function resolveSelect(
		key: typeof STORE_NAME | typeof store
	): PromiseifySelectors< ReportsSelect >;
}
