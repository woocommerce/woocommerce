/**
 * Internal dependencies
 */
import {
	PrevButtonInsideImage,
	NextButtonInsideImage,
	PrevButtonOutsideImage,
	NextButtonOutsideImage,
} from './icons';
import { NextPreviousButtonSettingValues } from './types';

export const getNextPreviousImagesWithClassName = (
	nextPreviousButtonsPosition: NextPreviousButtonSettingValues
) => {
	switch ( nextPreviousButtonsPosition ) {
		case 'insideTheImage':
			return {
				PrevButtonImage: PrevButtonInsideImage,
				NextButtonImage: NextButtonInsideImage,
				classname: 'inside-image',
			};
		case 'outsideTheImage':
			return {
				PrevButtonImage: PrevButtonOutsideImage,
				NextButtonImage: NextButtonOutsideImage,
				classname: 'outside-image',
			};
		case 'off':
			return null;
		default:
			return null;
	}
};
