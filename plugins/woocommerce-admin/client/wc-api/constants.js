/** @format */
/**
 * External dependencies
 */
import { MINUTE } from '@fresh-data/framework';

export const JETPACK_NAMESPACE = '/jetpack/v4';
export const NAMESPACE = '/wc/v4';
export const WC_ADMIN_NAMESPACE = '/wc-admin/v1';
export const WCS_NAMESPACE = '/wc/v1'; // WCS endpoints like Stripe are not avaiable on later /wc versions

export const DEFAULT_REQUIREMENT = {
	timeout: 1 * MINUTE,
	freshness: 30 * MINUTE,
};

// WordPress & WooCommerce both set a hard limit of 100 for the per_page parameter
export const MAX_PER_PAGE = 100;

export const DEFAULT_ACTIONABLE_STATUSES = [ 'processing', 'on-hold' ];

export const QUERY_DEFAULTS = {
	pageSize: 25,
	period: 'month',
	compare: 'previous_year',
};
