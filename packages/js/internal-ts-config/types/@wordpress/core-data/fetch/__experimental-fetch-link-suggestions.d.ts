declare module '@wordpress/core-data/build-types/fetch/__experimental-fetch-link-suggestions' {
	export type SearchOptions = {
	    /**
	     * Displays initial search suggestions, when true.
	     */
	    isInitialSuggestions?: boolean;
	    /**
	     * Search options for initial suggestions.
	     */
	    initialSuggestionsSearchOptions?: Omit<SearchOptions, 'isInitialSuggestions' | 'initialSuggestionsSearchOptions'>;
	    /**
	     * Filters by search type.
	     */
	    type?: 'attachment' | 'post' | 'term' | 'post-format';
	    /**
	     * Slug of the post-type or taxonomy.
	     */
	    subtype?: string;
	    /**
	     * Which page of results to return.
	     */
	    page?: number;
	    /**
	     * Search results per page.
	     */
	    perPage?: number;
	};
	export type EditorSettings = {
	    /**
	     * Disables post formats, when true.
	     */
	    disablePostFormats?: boolean;
	};
	export type SearchResult = {
	    /**
	     * Post or term id.
	     */
	    id: number;
	    /**
	     * Link url.
	     */
	    url: string;
	    /**
	     * Title of the link.
	     */
	    title: string;
	    /**
	     * The taxonomy or post type slug or type URL.
	     */
	    type: string;
	    /**
	     * Link kind of post-type or taxonomy
	     */
	    kind?: string;
	};
	/**
	 * Fetches link suggestions from the WordPress API.
	 *
	 * WordPress does not support searching multiple tables at once, e.g. posts and terms, so we
	 * perform multiple queries at the same time and then merge the results together.
	 *
	 * @param search
	 * @param searchOptions
	 * @param editorSettings
	 *
	 * @example
	 * ```js
	 * import { __experimentalFetchLinkSuggestions as fetchLinkSuggestions } from '@wordpress/core-data';
	 *
	 * //...
	 *
	 * export function initialize( id, settings ) {
	 *
	 * settings.__experimentalFetchLinkSuggestions = (
	 *     search,
	 *     searchOptions
	 * ) => fetchLinkSuggestions( search, searchOptions, settings );
	 * ```
	 */
	export default function fetchLinkSuggestions(search: string, searchOptions?: SearchOptions, editorSettings?: EditorSettings): Promise<SearchResult[]>;
	/**
	 * Sort search results by relevance to the given query.
	 *
	 * Sorting is necessary as we're querying multiple endpoints and merging the results. For example
	 * a taxonomy title might be more relevant than a post title, but by default taxonomy results will
	 * be ordered after all the (potentially irrelevant) post results.
	 *
	 * We sort by scoring each result, where the score is the number of tokens in the title that are
	 * also in the search query, divided by the total number of tokens in the title. This gives us a
	 * score between 0 and 1, where 1 is a perfect match.
	 *
	 * @param results
	 * @param search
	 */
	export declare function sortResults(results: SearchResult[], search: string): SearchResult[];
	/**
	 * Turns text into an array of tokens, with whitespace and punctuation removed.
	 *
	 * For example, `"I'm having a ball."` becomes `[ "im", "having", "a", "ball" ]`.
	 *
	 * @param text
	 */
	export declare function tokenize(text: string): string[];
}
