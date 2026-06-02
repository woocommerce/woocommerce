declare module '@wordpress/core-data/build-types/hooks/use-query-select' {
	export declare const META_SELECTORS: string[];
	/**
	 * Like useSelect, but the selectors return objects containing
	 * both the original data AND the resolution info.
	 *
	 * @since 6.1.0 Introduced in WordPress core.
	 * @private
	 *
	 * @param {Function} mapQuerySelect see useSelect
	 * @param {Array}    deps           see useSelect
	 *
	 * @example
	 * ```js
	 * import { useQuerySelect } from '@wordpress/data';
	 * import { store as coreDataStore } from '@wordpress/core-data';
	 *
	 * function PageTitleDisplay( { id } ) {
	 *   const { data: page, isResolving } = useQuerySelect( ( query ) => {
	 *     return query( coreDataStore ).getEntityRecord( 'postType', 'page', id )
	 *   }, [ id ] );
	 *
	 *   if ( isResolving ) {
	 *     return 'Loading...';
	 *   }
	 *
	 *   return page.title;
	 * }
	 *
	 * // Rendered in the application:
	 * // <PageTitleDisplay id={ 10 } />
	 * ```
	 *
	 * In the above example, when `PageTitleDisplay` is rendered into an
	 * application, the page and the resolution details will be retrieved from
	 * the store state using the `mapSelect` callback on `useQuerySelect`.
	 *
	 * If the id prop changes then any page in the state for that id is
	 * retrieved. If the id prop doesn't change and other props are passed in
	 * that do change, the title will not change because the dependency is just
	 * the id.
	 * @see useSelect
	 *
	 * @return {QuerySelectResponse} Queried data.
	 */
	export default function useQuerySelect(mapQuerySelect: any, deps: any): any;
}
