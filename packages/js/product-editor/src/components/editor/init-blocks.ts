/**
 * Internal dependencies
 */
import { init as initName } from '../details-name-block';
import { init as initSection } from '../section';
import { init as initTab } from '../tab';

export const initBlocks = () => {
	initName();
	initSection();
	initTab();
};
