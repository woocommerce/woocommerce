declare module '@wordpress/editor/build-types/components/post-publish-button' {
	export class PostPublishButton extends Component<any, any, any> {
	    constructor(props: any);
	    createOnClick(callback: any): (...args: any[]) => any;
	    closeEntitiesSavedStates(savedEntities: any): void;
	    state: {
	        entitiesSavedStatesCallback: boolean;
	    };
	    render(): import("react").JSX.Element;
	}
	declare const _default: unknown;
	export default _default;
	import { Component } from '@wordpress/element';
}
