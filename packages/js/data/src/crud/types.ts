/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types';

export type Item = {
	id: string;
	[ key: string ]: unknown;
};

export type ItemQuery = BaseQueryParams & {
	[ key: string ]: unknown;
};
