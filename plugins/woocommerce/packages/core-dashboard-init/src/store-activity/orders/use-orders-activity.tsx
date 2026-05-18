import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { receipt } from '@wordpress/icons';
import { WC_ORDER_ENTITY, type OrderRecord } from '../../data';
import { OrderTimelineEvent } from './order-timeline-event';

type ActivityState = 'loading' | 'empty' | 'success';

const QUERY_PARAMS = {
	per_page: 10,
	orderby: 'date',
	order: 'desc',
};

/**
 * Hook that fetches recent orders and exposes them as Store Activity events.
 *
 * Reads from the `woocommerce/order` entity registered in `data/`, which is
 * backed by the `/wc/v3/orders` REST endpoint.
 */
export function useOrdersActivity(): {
	state: ActivityState;
	events?: Array< {
		id: number;
		icon: JSX.Element;
		renderContent: () => JSX.Element;
		datetime: string;
	} >;
} {
	const { orders, isResolving } = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
		const records = select( coreStore ).getEntityRecords(
			WC_ORDER_ENTITY.kind,
			WC_ORDER_ENTITY.name,
			QUERY_PARAMS
		) as OrderRecord[] | null;

		return {
			orders: records,
			isResolving: select( coreStore ).isResolving(
				'getEntityRecords',
				[ WC_ORDER_ENTITY.kind, WC_ORDER_ENTITY.name, QUERY_PARAMS ]
			),
		};
	}, [] );

	if ( isResolving ) {
		return { state: 'loading' };
	}

	if ( ! orders?.length ) {
		return { state: 'empty' };
	}

	return {
		state: 'success',
		events: orders.map( ( order ) => ( {
			id: order.id,
			icon: receipt,
			renderContent: () => <OrderTimelineEvent order={ order } />,
			datetime: order.date_created_gmt ?? order.date_created ?? '',
		} ) ),
	};
}
