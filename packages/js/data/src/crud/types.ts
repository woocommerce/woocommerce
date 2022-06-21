/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types';

export type Resource = {
	id: number;
	[ key: string ]: unknown;
};

export type ResourceQuery = BaseQueryParams & {
	[ key: string ]: unknown;
};
