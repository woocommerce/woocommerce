/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types';

export type Item = {
	id: number;
	[ key: string ]: unknown;
};

export type ItemQuery = BaseQueryParams & {
	[ key: string ]: unknown;
};
