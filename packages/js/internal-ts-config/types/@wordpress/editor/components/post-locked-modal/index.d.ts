declare module '@wordpress/editor/build-types/components/post-locked-modal' {
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
	} | typeof PostLockedModal;
	export default _default;
	declare function PostLockedModal(): import("react").JSX.Element | null;
}
