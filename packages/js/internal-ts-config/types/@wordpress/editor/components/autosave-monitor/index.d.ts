declare module '@wordpress/editor/build-types/components/autosave-monitor' {
	export class AutosaveMonitor extends Component<any, any, any> {
	    constructor(props: any);
	    needsAutosave: boolean;
	    componentDidMount(): void;
	    componentDidUpdate(prevProps: any): void;
	    componentWillUnmount(): void;
	    setAutosaveTimer(timeout?: number): void;
	    timerId: number | undefined;
	    autosaveTimerHandler(): void;
	    render(): null;
	}
	declare const _default: unknown;
	export default _default;
	import { Component } from '@wordpress/element';
}
