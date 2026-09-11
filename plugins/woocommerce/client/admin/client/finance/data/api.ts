/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { FINANCE_API_PATH } from '../constants';

export type PayoutsPageQuery = {
	cursor: string | null;
	perPage: number;
};

export const getProvidersPath = (): string => `${ FINANCE_API_PATH }/providers`;

export const getBalancesPath = ( providerId: string ): string =>
	`${ FINANCE_API_PATH }/providers/${ encodeURIComponent(
		providerId
	) }/balance`;

export const getPayoutsPath = (
	providerId: string,
	{ cursor, perPage }: PayoutsPageQuery
): string =>
	addQueryArgs(
		`${ FINANCE_API_PATH }/providers/${ encodeURIComponent(
			providerId
		) }/payouts`,
		{
			per_page: perPage,
			...( cursor ? { next_cursor: cursor } : {} ),
		}
	);
