/**
 * Internal dependencies
 */
import { init as initSection } from '../section';
import { init as initImages } from '../images';
import { init as initName } from '../name';
import { init as initSummary } from '../summary';

export const initBlocks = () => {
	initImages();
	initName();
	initSection();
	initSummary();
};
