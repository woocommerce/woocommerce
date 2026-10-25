export type PersonalizationTagsQuery = {
	context: string;
	per_page: number;
	post_id?: number | string;
};

// Keyed by post id, so alternating between posts does not thrash a single slot.
// Bounded by the number of posts opened in one page load.
const queryCache = new Map<
	number | string | undefined,
	PersonalizationTagsQuery
>();

/**
 * Builds the entity query used to fetch personalization tags, returning the
 * same object for a given post id.
 *
 * Two call sites depend on this being one definition. `getPersonalizationTagsList`
 * runs once per block on every store change and passes the query to
 * `getEntityRecords`, which forwards it to core-data's `getQueriedItems`; rememo
 * memoizes that by argument reference, so a fresh object literal misses on every
 * call and prepends a node to a list only discarded when the entity state
 * changes. `invalidatePersonalizationTagsCache` passes the same query to
 * `invalidateResolution`, which matches resolver arguments structurally — if the
 * two shapes ever drifted, invalidation would silently stop matching rather than
 * fail loudly.
 *
 * @param postId Id of the post being edited, when there is one.
 * @return Query to pass to `getEntityRecords`.
 */
export function getPersonalizationTagsQuery(
	postId: number | string | undefined
): PersonalizationTagsQuery {
	let query = queryCache.get( postId );

	if ( ! query ) {
		query = {
			context: 'view',
			per_page: -1,
			// Include post_id for context-aware tag filtering (e.g., automation emails)
			...( postId ? { post_id: postId } : {} ),
		};
		queryCache.set( postId, query );
	}

	return query;
}
