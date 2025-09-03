/**
 * External dependencies
 */
import { registerCoreBlocks } from '@wordpress/block-library';
import { unregisterFormatType } from '@wordpress/rich-text';

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
import { modifyMoveToTrashAction } from './core/move-to-trash';
import { enhanceSiteLogoBlock } from './core/site-logo';

export { getAllowedBlockNames } from './utils';

export function initBlocks() {
	// Footnote is registered by every register core block call as a side effect and causes warnings.
	unregisterFormatType( 'core/footnote' );
	registerCoreBlocks();
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
	modifyMoveToTrashAction();
	enhanceSiteLogoBlock();
	removeBlockStylesFromAllBlocks();
}
