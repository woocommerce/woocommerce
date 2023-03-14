/**
 * External dependencies
 */
import { registerCoreBlocks } from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import { init as initName } from '../details-name-block';
import { init as initSection } from '../section';
import { init as initTab } from '../tab';
import { init as initPricing } from '../pricing-block';

export const initBlocks = () => {
	registerCoreBlocks();
	initName();
	initSection();
	initTab();
	initPricing();
};
