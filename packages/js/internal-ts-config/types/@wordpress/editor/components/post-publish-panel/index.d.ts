declare module '@wordpress/editor/build-types/components/post-publish-panel' {
	export class PostPublishPanel extends Component<any, any, any> {
	    constructor(...args: any[]);
	    onSubmit(): void;
	    cancelButtonNode: import("react").RefObject<any>;
	    componentDidMount(): void;
	    timeoutID: number | undefined;
	    componentWillUnmount(): void;
	    componentDidUpdate(prevProps: any): void;
	    render(): import("react").JSX.Element;
	}
	declare const _default: unknown;
	export default _default;
	import { Component } from '@wordpress/element';
}
