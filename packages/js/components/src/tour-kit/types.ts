/**
 * External dependencies
 */
import {
	Step,
	Options,
	Config,
	TourStepRendererProps,
} from '@automattic/tour-kit';

export interface WooStep extends Step {
	meta: {
		/** Unique name for step, mainly used for tracking. */
		name: string | null;
		heading: string | React.ReactElement | null;
		descriptions: {
			desktop: string | React.ReactElement | null;
			mobile?: string | React.ReactElement | null;
		};
		primaryButton?: {
			/** Set a text for the button. Default to "Done" for the last step and "Next" for the other steps  */
			text?: string;
			/** Disable the button or not. Default to False */
			isDisabled?: boolean;
			isHidden?: boolean;
		};
		secondaryButton?: {
			/** Set a text for the button. Default to "Back" */
			text?: string;
		};
		skipButton?: {
			/** Set a text for the button. Default to "Skip" */
			text?: string;
			isVisible?: boolean;
		};
		onBeforeStep?: () => void;
	};
	/** Auto apply the focus state for the element. Default to null */
	focusElement?: {
		desktop?: string;
		mobile?: string;
		/** Iframe HTML selector. Default to null. If set, it will find the focus element in the iframe */
		iframe?: string;
	};
}
export type WooOptions = Options;

export interface WooConfig extends Omit< Config, 'renderers' | 'isMinimized' > {
	steps: WooStep[];
	options?: WooOptions;
}

export interface WooTourStepRendererProps extends TourStepRendererProps {
	steps: WooStep[];
}

export { CloseHandler } from '@automattic/tour-kit';
