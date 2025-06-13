/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { Color, FilterBlockContext } from '../../types';

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
	context: FilterBlockContext;

	sliderHandle: Color;
	setSliderHandle: ( color: string ) => void;

	sliderHandleBorder: Color;
	setSliderHandleBorder: ( color: string ) => void;

	slider: Color;
	setSlider: ( color: string ) => void;
}
