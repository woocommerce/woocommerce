declare module '@wordpress/editor/build-types/components/post-taxonomies/hierarchical-term-selector' {
	/**
	 * Sort Terms by Selected.
	 *
	 * @param {Object[]} termsTree Array of terms in tree format.
	 * @param {number[]} terms     Selected terms.
	 *
	 * @return {Object[]} Sorted array of terms.
	 */
	export function sortBySelected(termsTree: Object[], terms: number[]): Object[];
	/**
	 * Find term by parent id or name.
	 *
	 * @param {Object[]}      terms  Array of Terms.
	 * @param {number|string} parent id.
	 * @param {string}        name   Term name.
	 * @return {Object} Term object.
	 */
	export function findTerm(terms: Object[], parent: number | string, name: string): Object;
	/**
	 * Get filter matcher function.
	 *
	 * @param {string} filterValue Filter value.
	 * @return {(function(Object): (Object|boolean))} Matcher function.
	 */
	export function getFilterMatcher(filterValue: string): ((arg0: Object) => (Object | boolean));
	/**
	 * Hierarchical term selector.
	 *
	 * @param {Object} props      Component props.
	 * @param {string} props.slug Taxonomy slug.
	 * @return {Element}        Hierarchical term selector component.
	 */
	export function HierarchicalTermSelector({ slug }: {
	    slug: string;
	}): Element;
	declare const _default: {
	    new (props: {
	        [key: string]: any;
	    }): {
	        componentDidMount(): void;
	        componentWillUnmount(): void;
	        render(): import(
	        /**
	         * Sort Terms by Selected.
	         *
	         * @param {Object[]} termsTree Array of terms in tree format.
	         * @param {number[]} terms     Selected terms.
	         *
	         * @return {Object[]} Sorted array of terms.
	         */
	        "react").JSX.Element;
	        context: unknown;
	        setState<K extends never>(state: {} | ((prevState: Readonly<{}>, props: Readonly<{}>) => {} | Pick<{}, K> | null) | Pick<{}, K> | null, callback?: (() => void) | undefined): void;
	        forceUpdate(callback?: (() => void) | undefined): void;
	        readonly props: Readonly<{}>;
	        state: Readonly<{}>;
	        refs: {
	            [key: string]: import("react").ReactInstance;
	        };
	        shouldComponentUpdate?(nextProps: Readonly<{}>, nextState: Readonly<{}>, nextContext: any): boolean;
	        componentDidCatch?(error: Error, errorInfo: import("react").ErrorInfo): void;
	        getSnapshotBeforeUpdate?(prevProps: Readonly<{}>, prevState: Readonly<{}>): any;
	        componentDidUpdate?(prevProps: Readonly<{}>, prevState: Readonly<{}>, snapshot?: any): void;
	        componentWillMount?(): void;
	        UNSAFE_componentWillMount?(): void;
	        componentWillReceiveProps?(nextProps: Readonly<{}>, nextContext: any): void;
	        UNSAFE_componentWillReceiveProps?(nextProps: Readonly<{}>, nextContext: any): void;
	        componentWillUpdate?(nextProps: Readonly<{}>, nextState: Readonly<{}>, nextContext: any): void;
	        UNSAFE_componentWillUpdate?(nextProps: Readonly<{}>, nextState: Readonly<{}>, nextContext: any): void;
	    };
	    instances: {
	        componentDidMount(): void;
	        componentWillUnmount(): void;
	        render(): import("react").JSX.Element;
	        context: unknown;
	        setState<K extends never>(state: {} | ((prevState: Readonly<{}>, props: Readonly<{}>) => {} | Pick<{}, K> | null) | Pick<{}, K> | null, callback?: (() => void) | undefined): void;
	        forceUpdate(callback?: (() => void) | undefined): void;
	        readonly props: Readonly<{}>;
	        state: Readonly<{}>;
	        refs: {
	            [key: string]: import("react").ReactInstance;
	        };
	        shouldComponentUpdate?(nextProps: Readonly<{}>, nextState: Readonly<{}>, nextContext: any): boolean;
	        componentDidCatch?(error: Error, errorInfo: import("react").ErrorInfo): void;
	        getSnapshotBeforeUpdate?(prevProps: Readonly<{}>, prevState: Readonly<{}>): any;
	        componentDidUpdate?(prevProps: Readonly<{}>, prevState: Readonly<{}>, snapshot?: any): void;
	        componentWillMount?(): void;
	        UNSAFE_componentWillMount?(): void;
	        componentWillReceiveProps?(nextProps: Readonly<{}>, nextContext: any): void;
	        UNSAFE_componentWillReceiveProps?(nextProps: Readonly<{}>, nextContext: any): void;
	        componentWillUpdate?(nextProps: Readonly<{}>, nextState: Readonly<{}>, nextContext: any): void;
	        UNSAFE_componentWillUpdate?(nextProps: Readonly<{}>, nextState: Readonly<{}>, nextContext: any): void;
	    }[];
	    contextType?: import("react").Context<any> | undefined;
	};
	export default _default;
}
