import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { starFilled } from '@wordpress/icons';
import type { ReviewRecord } from '../../data';
import { ReviewTimelineEvent } from './review-timeline-event';

type ActivityState = 'loading' | 'empty' | 'success';

/**
 * Product reviews ride the stock `root/comment` entity that
 * `@wordpress/core-data` already registers — no entity definition lives
 * in our `data/` for this source. `type: 'review'` filters the comment
 * collection to WooCommerce product reviews; `_embed: 'up'` asks the REST
 * API to inline the parent product so we can render its title without a
 * follow-up request.
 */
const QUERY_PARAMS = {
	per_page: 10,
	orderby: 'date',
	order: 'desc',
	type: 'review',
	_embed: 'up',
};

/**
 * Hook that fetches recent product reviews and exposes them as Store
 * Activity events.
 */
export function useReviewsActivity(): {
	state: ActivityState;
	events?: Array< {
		id: number;
		icon: JSX.Element;
		renderContent: () => JSX.Element;
		datetime: string;
	} >;
} {
	const { reviews, isResolving } = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
		const records = select( coreStore ).getEntityRecords(
			'root',
			'comment',
			QUERY_PARAMS
		) as ReviewRecord[] | null;

		return {
			reviews: records,
			isResolving: select( coreStore ).isResolving(
				'getEntityRecords',
				[ 'root', 'comment', QUERY_PARAMS ]
			),
		};
	}, [] );

	if ( isResolving ) {
		return { state: 'loading' };
	}

	if ( ! reviews?.length ) {
		return { state: 'empty' };
	}

	return {
		state: 'success',
		events: reviews.map( ( review ) => ( {
			id: review.id,
			icon: starFilled,
			renderContent: () => <ReviewTimelineEvent review={ review } />,
			datetime: review.date_gmt,
		} ) ),
	};
}
