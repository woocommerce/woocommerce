import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { starFilled } from '@wordpress/icons';
import {
	WC_PRODUCT_REVIEW_ENTITY,
	type ProductReviewRecord,
} from '../../data';
import { ReviewTimelineEvent } from './review-timeline-event';

type ActivityState = 'loading' | 'empty' | 'success';

/**
 * Reads from the WC product reviews entity registered in `data/`, backed by
 * `/wc/v3/products/reviews`. We do NOT go through `root/comment` with
 * `type='review'`: that filter only matches by `comment_type` (a naming
 * convention) and has no guarantee the comment's parent is a product.
 *
 * Limit `status` to `approved` so unpublished/spam reviews don't surface in
 * the dashboard timeline.
 */
const QUERY_PARAMS = {
	per_page: 10,
	orderby: 'date',
	order: 'desc',
	status: 'approved',
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
			WC_PRODUCT_REVIEW_ENTITY.kind,
			WC_PRODUCT_REVIEW_ENTITY.name,
			QUERY_PARAMS
		) as ProductReviewRecord[] | null;

		return {
			reviews: records,
			isResolving: select( coreStore ).isResolving(
				'getEntityRecords',
				[
					WC_PRODUCT_REVIEW_ENTITY.kind,
					WC_PRODUCT_REVIEW_ENTITY.name,
					QUERY_PARAMS,
				]
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
			datetime: review.date_created_gmt ?? review.date_created ?? '',
		} ) ),
	};
}
