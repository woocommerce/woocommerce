/**
 * External dependencies
 */
import { BlockInstance, getBlockType } from '@wordpress/blocks';
import {
	registerCoreBlocks,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore We need this to import the block modules for registration.
	__experimentalGetCoreBlocks,
} from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import { init as initImages } from '../images';
import { init as initName } from '../details-name-block';
import { init as initRadio } from '../radio';
import { init as initSummary } from '../details-summary-block';
import { init as initSection } from '../section';
import { init as initTab } from '../tab';
import { init as initPricing } from '../pricing-block';
import { init as initCollapsible } from '../collapsible-block';
import { init as initScheduleSale } from '../../blocks/schedule-sale';
import { init as initTrackInventory } from '../../blocks/track-inventory';

export const initBlocks = () => {
	const coreBlocks = __experimentalGetCoreBlocks();
	const blocks = coreBlocks.filter( ( block: BlockInstance ) => {
		return ! getBlockType( block.name );
	} );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore An argument is allowed to specify which blocks to register.
	registerCoreBlocks( blocks );

	initImages();
	initName();
	initRadio();
	initSummary();
	initSection();
	initTab();
	initPricing();
	initCollapsible();
	initScheduleSale();
	initTrackInventory();
};
