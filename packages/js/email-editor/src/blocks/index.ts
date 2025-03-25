/**
 * External dependencies
 */
import { registerCoreBlocks } from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import { enhanceColumnBlock } from './core/column';
import {
	disableColumnsLayout,
	deactivateStackOnMobile,
	enhanceColumnsBlock,
} from './core/columns';
import { enhancePostContentBlock } from './core/post-content';
import { disableGroupVariations } from './core/group';
import { disableImageFilter, hideExpandOnClick } from './core/image';
import {
	disableCertainRichTextFormats,
	extendRichTextFormats,
	activatePersonalizationTagsReplacing,
} from './core/rich-text';
import { enhanceButtonBlock } from './core/button';
import { enhanceButtonsBlock } from './core/buttons';
import { alterSupportConfiguration } from './core/general-block-support';

export function initBlocks() {
	deactivateStackOnMobile();
	hideExpandOnClick();
	disableImageFilter();
	disableCertainRichTextFormats();
	disableColumnsLayout();
	disableGroupVariations();
	enhanceButtonBlock();
	enhanceButtonsBlock();
	enhanceColumnBlock();
	enhanceColumnsBlock();
	enhancePostContentBlock();
	extendRichTextFormats();
	activatePersonalizationTagsReplacing();
	alterSupportConfiguration();
	registerCoreBlocks();
}
