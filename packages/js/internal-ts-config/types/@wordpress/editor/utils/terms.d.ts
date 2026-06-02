declare module '@wordpress/editor/build-types/utils/terms' {
	/**
	 * Returns terms in a tree form.
	 *
	 * @param {Array} flatTerms Array of terms in flat format.
	 *
	 * @return {Array} Array of terms in tree format.
	 */
	export function buildTermsTree(flatTerms: any[]): any[];
	export function unescapeString(arg: any): string;
	export function unescapeTerm(term: Object): Object;
	export function unescapeTerms(terms: Object[]): Object[];
}
