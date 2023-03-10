/**
 * Internal dependencies
 */
import { init as initSection } from '../section';
import { init as initTab } from '../tab';

export const initBlocks = () => {
	initSection();
	initTab();
};
