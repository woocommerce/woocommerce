import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { people } from '@wordpress/icons';
import { WC_CUSTOMER_ENTITY, type CustomerRecord } from '../../data';
import type { ActivityHookResult } from '../types';
import { CustomerTimelineEvent } from './customer-timeline-event';

const QUERY_PARAMS = {
	per_page: 10,
	orderby: 'registered_date',
	order: 'desc',
};

/**
 * Hook that fetches recently-registered WooCommerce customers and exposes
 * them as Store Activity events.
 *
 * The endpoint (`/wc/v3/customers`) returns registered customers only —
 * guest checkouts don't show up here. Surfacing guest activity (when
 * relevant) is the orders source's job.
 */
export function useCustomersActivity(): ActivityHookResult {
	const { customers, isResolving } = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
		const records = select( coreStore ).getEntityRecords(
			WC_CUSTOMER_ENTITY.kind,
			WC_CUSTOMER_ENTITY.name,
			QUERY_PARAMS
		) as CustomerRecord[] | null;

		return {
			customers: records,
			isResolving: select( coreStore ).isResolving(
				'getEntityRecords',
				[
					WC_CUSTOMER_ENTITY.kind,
					WC_CUSTOMER_ENTITY.name,
					QUERY_PARAMS,
				]
			),
		};
	}, [] );

	if ( isResolving ) {
		return { state: 'loading' };
	}

	if ( ! customers?.length ) {
		return { state: 'empty' };
	}

	return {
		state: 'success',
		events: customers.map( ( customer ) => ( {
			id: customer.id,
			icon: people,
			renderContent: () => (
				<CustomerTimelineEvent customer={ customer } />
			),
			datetime:
				customer.date_created_gmt ?? customer.date_created ?? '',
		} ) ),
	};
}
