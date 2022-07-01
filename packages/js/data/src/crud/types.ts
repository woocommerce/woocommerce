/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types';

export type IdType = number | string;

export type Item = {
	id: IdType;
	[ key: string ]: unknown;
};

export type ItemQuery = BaseQueryParams & {
	[ key: string ]: unknown;
};
