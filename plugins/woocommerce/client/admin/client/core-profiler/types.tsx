/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '.';

export type ComponentMeta = {
	/** React component that is rendered when state matches the location this meta key is defined */
	component: ( arg0: ComponentProps ) => JSX.Element;
	/** Number between 0 - 100 */
	progress: number;
};

export type ComponentProps = {
	navigationProgress: number | undefined;
	sendEvent: unknown;
	context: CoreProfilerStateMachineContext;
};
