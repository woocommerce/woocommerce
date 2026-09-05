export const REVIEW_PAGE_LIMIT = 5;

// Still used for the Reviews panel's own cache invalidation — the count
// itself now comes from activityPanelStore's getActivityPanelCounts().
export const unapprovedReviewsQuery = {
	page: 1,
	per_page: 1,
	status: 'hold',
	_embed: 1,
	_fields: [ 'id' ],
};
