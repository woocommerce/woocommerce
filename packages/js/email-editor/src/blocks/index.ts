/**
 * External dependencies
 */
import { registerCoreBlocks } from '@wordpress/block-library';
import { getBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	disableColumnsLayoutAndEnhanceColumnsBlock,
	deactivateStackOnMobile,
} from './core/columns';
import { enhancePostContentBlock } from './core/post-content';
import { disableGroupVariations } from './core/group';
import { disableImageFilter, hideExpandOnClick } from './core/image';
import {
	disableCertainRichTextFormats,
	extendRichTextFormats,
	activatePersonalizationTagsReplacing,
} from './core/rich-text';
import { enhanceButtonsBlock } from './core/buttons';
import {
	alterSupportConfiguration,
	removeBlockStylesFromAllBlocks,
} from './core/general-block-support';
import { enhanceQuoteBlock } from './core/quote';
import { filterSetUrlAttribute } from './core/block-edit';
import { enhanceSocialLinksBlock } from './core/social-links';
import { enhanceSiteLogoBlock } from './core/site-logo';

export { getAllowedBlockNames } from './utils';

export function initBlocks() {
	// Check if core blocks are already registered by looking for a fundamental core block
	// 'core/paragraph' is always included in core blocks
	if ( ! getBlockType( 'core/paragraph' ) ) {
		registerCoreBlocks();
	}
	filterSetUrlAttribute();
	deactivateStackOnMobile();
	hideExpandOnClick();
	disableImageFilter();
	disableCertainRichTextFormats();
	disableColumnsLayoutAndEnhanceColumnsBlock();
	disableGroupVariations();
	enhanceButtonsBlock();
	enhancePostContentBlock();
	enhanceQuoteBlock();
	extendRichTextFormats();
	activatePersonalizationTagsReplacing();
	alterSupportConfiguration();
	enhanceSocialLinksBlock();
	enhanceSiteLogoBlock();
	removeBlockStylesFromAllBlocks();
}
