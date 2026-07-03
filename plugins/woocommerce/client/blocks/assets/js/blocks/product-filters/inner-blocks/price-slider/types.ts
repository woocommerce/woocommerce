/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { RangeInputBlockContext } from '../../../../types/type-defs/range-input';

type Color = {
	slug?: string;
	class?: string;
	name?: string;
	color: string;
};

export type BlockAttributes = {
	showInputFields: boolean;
	inlineInput: boolean;

	sliderHandle: string;
	customSliderHandle: string;

	sliderHandleBorder: string;
	customSliderHandleBorder: string;

	slider: string;
	customSlider: string;
};

export interface EditProps extends BlockEditProps< BlockAttributes > {
	context: RangeInputBlockContext;

	sliderHandle: Color;
	setSliderHandle: ( color: string ) => void;

	sliderHandleBorder: Color;
	setSliderHandleBorder: ( color: string ) => void;

	slider: Color;
	setSlider: ( color: string ) => void;
}
