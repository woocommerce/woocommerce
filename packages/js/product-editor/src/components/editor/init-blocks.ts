/**
 * Internal dependencies
 */
import { init as initName } from '../details-name-block';
import { init as initSection } from '../section';

export const initBlocks = () => {
	initName();
	initSection();
};
