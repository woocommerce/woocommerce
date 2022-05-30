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
		heading: string | null;
		descriptions: {
			desktop: string | React.ReactElement | null;
			mobile?: string | React.ReactElement | null;
		};
		primaryButtonText?: string;
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
