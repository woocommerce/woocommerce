/**
 * Internal dependencies
 */
import { init as initName } from '../details-name-block';
import { init as initSection } from '../section';
import { init as initTab } from '../tab';
import * as listPriceBlock from '../list-price';

export const initBlocks = () => {
	initName();
	initSection();
	initTab();
	listPriceBlock.init();
};
