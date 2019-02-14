/** @format */
/**
 * External dependencies
 */
import { MINUTE } from '@fresh-data/framework';

export const NAMESPACE = '/wc/v4';

// @todo Remove once swagger endpoints are phased out.
export const SWAGGERNAMESPACE = 'https://virtserver.swaggerhub.com/peterfabian/wc-v3-api/1.0.0/';

export const DEFAULT_REQUIREMENT = {
	timeout: 1 * MINUTE,
	freshness: 30 * MINUTE,
};

// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter
export const MAX_PER_PAGE = 100;

export const QUERY_DEFAULTS = {
	pageSize: 25,
	period: 'month',
	compare: 'previous_year',
};
