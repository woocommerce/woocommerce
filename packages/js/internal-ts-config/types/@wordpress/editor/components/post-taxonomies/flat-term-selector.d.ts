declare module '@wordpress/editor/build-types/components/post-taxonomies/flat-term-selector' {
	/**
	 * Renders a flat term selector component.
	 *
	 * @param {Object}  props                         The component props.
	 * @param {string}  props.slug                    The slug of the taxonomy.
	 * @param {boolean} props.__nextHasNoMarginBottom Start opting into the new margin-free styles that will become the default in a future version, currently scheduled to be WordPress 7.0. (The prop can be safely removed once this happens.)
	 *
	 * @return {React.ReactNode} The rendered flat term selector component.
	 */
	export function FlatTermSelector({ slug, __nextHasNoMarginBottom }: {
	    slug: string;
	    __nextHasNoMarginBottom: boolean;
	}): React.ReactNode;
	declare const _default: {
	    new (props: {
	        [key: string]: any;
	    }): {
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
