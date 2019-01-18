/** @format */
/**
 * External dependencies
 */
import { SECOND, MINUTE } from '@fresh-data/framework';

export const NAMESPACE = '/wc/v4';

export const DEFAULT_REQUIREMENT = {
	timeout: 5 * SECOND,
	freshness: 5 * MINUTE,
};

// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter
export const MAX_PER_PAGE = 100;

export const QUERY_DEFAULTS = {
	pageSize: 25,
	period: 'month',
	compare: 'previous_year',
};
