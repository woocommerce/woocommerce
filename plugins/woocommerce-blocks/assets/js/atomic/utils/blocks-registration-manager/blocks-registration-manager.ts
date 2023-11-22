/**
 * External dependencies
 */
import { getBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	TemplateChangeDetector,
	TemplateChangeDetectorObserver,
} from './template-change-detector';
import {
	BlockRegistrationStrategy,
	BlockTypeStrategy,
	BlockVariationStrategy,
} from './block-registration-strategy';
import { BLOCKS_WITH_RESTRICTION } from './blocks-with-restriction';

/**
 * Manages the registration and unregistration of blocks based on template or page restrictions.
 *
 * This class implements the TemplateChangeDetectorObserver interface and is responsible for managing the registration and unregistration of blocks based on the restrictions defined in the BLOCKS_WITH_RESTRICTION constant.
 *
 * The class maintains a list of unregistered blocks and uses a block registration strategy to register and unregister blocks as needed. The strategy used depends on whether the block is a variation block or a regular block.
 *
 * The `run` method is the main entry point for the class. It is called with a TemplateChangeDetector object and registers and unregisters blocks based on the current template and whether the editor is in post or page mode.
 */
export class BlockRegistrationManager
	implements TemplateChangeDetectorObserver
{
	private unregisteredBlocks: string[] = [];
	private blockRegistrationStrategy: BlockRegistrationStrategy;

	constructor() {
		this.blockRegistrationStrategy = new BlockTypeStrategy();
	}

	/**
	 * Determines whether a block should be registered based on the current template or page.
	 *
	 * This method checks whether a block with restrictions should be registered based on the current template ID and
	 * whether the editor is in post or page mode. It checks whether the current template ID starts with any of the
	 * allowed templates or template parts for the block, and whether the block is available in the post or page editor.
	 *
	 * @param {Object}  params                          - The parameters for the method.
	 * @param {string}  params.blockWithRestrictionName - The name of the block with restrictions.
	 * @param {string}  params.currentTemplateId        - The ID of the current template.
	 * @param {boolean} params.isPostOrPage             - Whether the editor is in a post or page.
	 * @return {boolean} True if the block should be registered, false otherwise.
	 */
	private shouldBlockBeRegistered( {
		blockWithRestrictionName,
		currentTemplateId,
		isPostOrPage,
	}: {
		blockWithRestrictionName: string;
		currentTemplateId: string;
		isPostOrPage: boolean;
	} ) {
		const {
			allowedTemplates,
			allowedTemplateParts,
			availableInPostOrPageEditor,
		} = BLOCKS_WITH_RESTRICTION[ blockWithRestrictionName ];
		const shouldBeAvailableOnTemplate = Object.keys(
			allowedTemplates
		).some( ( allowedTemplate ) =>
			currentTemplateId.startsWith( allowedTemplate )
		);
		const shouldBeAvailableOnTemplatePart = Object.keys(
			allowedTemplateParts
		).some( ( allowedTemplate ) =>
			currentTemplateId.startsWith( allowedTemplate )
		);
		const shouldBeAvailableOnPostOrPageEditor =
			isPostOrPage && availableInPostOrPageEditor;

		return (
			shouldBeAvailableOnTemplate ||
			shouldBeAvailableOnTemplatePart ||
			shouldBeAvailableOnPostOrPageEditor
		);
	}

	/**
	 * Unregisters blocks before entering a restricted area based on the current template or page/post.
	 *
	 * This method iterates over all blocks with restrictions and unregisters them if they should not be registered
	 * based on the current template ID and whether the editor is in a post or page. It uses a block registration
	 * strategy to unregister the blocks, which depends on whether the block is a variation block or a regular block.
	 *
	 * @param {Object}  params                   - The parameters for the method.
	 * @param {string}  params.currentTemplateId - The ID of the current template.
	 * @param {boolean} params.isPostOrPage      - Whether the editor is in post or page mode.
	 */
	unregisterBlocksBeforeEnteringRestrictedArea( {
		currentTemplateId,
		isPostOrPage,
	}: {
		currentTemplateId: string;
		isPostOrPage: boolean;
	} ) {
		for ( const blockWithRestrictionName of Object.keys(
			BLOCKS_WITH_RESTRICTION
		) ) {
			if (
				this.shouldBlockBeRegistered( {
					blockWithRestrictionName,
					currentTemplateId,
					isPostOrPage,
				} )
			) {
				continue;
			}

			if ( ! getBlockType( blockWithRestrictionName ) ) {
				continue;
			}

			this.blockRegistrationStrategy = BLOCKS_WITH_RESTRICTION[
				blockWithRestrictionName
			].isVariationBlock
				? new BlockVariationStrategy()
				: new BlockTypeStrategy();

			this.blockRegistrationStrategy.unregister(
				blockWithRestrictionName
			);
			this.unregisteredBlocks.push( blockWithRestrictionName );
		}
	}

	/**
	 * Registers blocks after leaving a restricted area.
	 *
	 * This method iterates over all unregistered blocks and registers them if they are not restricted in the current context.
	 * It uses a block registration strategy to register the blocks, which depends on whether the block is a variation block or a regular block.
	 * If the block is successfully registered, it is removed from the list of unregistered blocks.
	 */
	registerBlocksAfterLeavingRestrictedArea() {
		for ( const unregisteredBlockName of this.unregisteredBlocks ) {
			if ( ! getBlockType( unregisteredBlockName ) ) {
				continue;
			}

			const restrictedBlockData =
				BLOCKS_WITH_RESTRICTION[ unregisteredBlockName ];
			this.blockRegistrationStrategy = BLOCKS_WITH_RESTRICTION[
				unregisteredBlockName
			].isVariationBlock
				? new BlockVariationStrategy()
				: new BlockTypeStrategy();
			const isBlockRegistered = this.blockRegistrationStrategy.register(
				restrictedBlockData.blockMetadata,
				restrictedBlockData.blockSettings
			);
			this.unregisteredBlocks = isBlockRegistered
				? this.unregisteredBlocks.filter(
						( blockName ) => blockName !== unregisteredBlockName
				  )
				: this.unregisteredBlocks;
		}
	}

	/**
	 * Runs the block registration manager.
	 *
	 * This method is the main entry point for the block registration manager. It is called with a TemplateChangeDetector object,
	 * and registers and unregisters blocks based on the current template and whether the editor is in a post or page.
	 *
	 * @param {TemplateChangeDetector} templateChangeDetector - The template change detector object.
	 */
	run( templateChangeDetector: TemplateChangeDetector ) {
		this.registerBlocksAfterLeavingRestrictedArea();
		this.unregisterBlocksBeforeEnteringRestrictedArea( {
			currentTemplateId:
				templateChangeDetector.getCurrentTemplateId() || '',
			isPostOrPage: templateChangeDetector.getIsPostOrPage(),
		} );
	}
}
