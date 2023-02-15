/**
 * Internal dependencies
 */
import { init as initDetailsSection } from '../details-section';
import { init as initImages } from '../images';
import { init as initName } from '../name';
import { init as initSummary } from '../summary';

export const initBlocks = () => {
	initDetailsSection();
	initImages();
	initName();
	initSummary();
};
