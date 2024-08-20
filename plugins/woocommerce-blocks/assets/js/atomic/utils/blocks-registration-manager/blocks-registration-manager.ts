/**
 * External dependencies
 */
import { getBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	EditorViewChangeDetector,
	EditorViewChangeDetectorObserver,
	EditorViewContentType,
} from './editor-view-change-detector';
import {
	BlockRegistrationStrategy,
	BlockTypeStrategy,
	BlockVariationStrategy,
} from './block-registration-strategy';
import { BLOCKS_WITH_RESTRICTION } from './blocks-with-restriction';
/**
 * Manages the registration and unregistration of blocks based on template or page restrictions.
 *
 * This class implements the EditorViewChangeDetectorObserver interface and is responsible for managing the registration and unregistration of blocks based on the restrictions defined in the BLOCKS_WITH_RESTRICTION constant.
 *
 * The class maintains a list of unregistered blocks and uses a block registration strategy to register and unregister blocks as needed. The strategy used depends on whether the block is a variation block or a regular block.
 *
 * The `run` method is the main entry point for the class. It is called with a EditorViewChangeDetector object and registers and unregisters blocks based on the current template and whether the editor is in post or page mode.
 */
export class BlockRegistrationManager
	implements EditorViewChangeDetectorObserver
{
	private unregisteredBlocks: string[] = [];
	private blockRegistrationStrategy: BlockRegistrationStrategy;

	constructor() {
		this.blockRegistrationStrategy = new BlockTypeStrategy();
	}

	/**
	 * Determines whether a block should be registered based on the current post, page, template or template part.
	 *
	 * This method checks whether a block with restrictions should be registered based on the current content ID and
	 * whether the editor is in post, page, template or template part mode. It checks whether the current template ID starts with any of the
	 * allowed templates or template parts for the block, and whether the block is available in the post or page editor.
	 *
	 * @param {Object}  params                          - The parameters for the method.
	 * @param {string}  params.blockWithRestrictionName - The name of the block with restrictions.
	 * @param {string}  params.currentContentId         - The ID of the current template.
	 * @param {boolean} params.currentContentType       - The type of the current content.
	 * @return {boolean} True if the block should be registered, false otherwise.
	 */
	private shouldBlockBeRegistered( {
		blockWithRestrictionName,
		currentContentId,
		currentContentType,
	}: {
		blockWithRestrictionName: string;
		currentContentId: string;
		currentContentType: EditorViewContentType;
	} ) {
		const {
			allowedTemplates,
			allowedTemplateParts,
			availableInPostEditor,
			availableInPageEditor,
		} = BLOCKS_WITH_RESTRICTION[ blockWithRestrictionName ];

		const shouldBeAvailableOnTemplate = allowedTemplates
			? Object.keys( allowedTemplates ).some(
					( allowedTemplate ) =>
						currentContentId.startsWith( allowedTemplate ) &&
						allowedTemplates[ allowedTemplate ]
			  )
			: currentContentType === EditorViewContentType.WP_TEMPLATE;
		const shouldBeAvailableOnTemplatePart = allowedTemplateParts
			? Object.keys( allowedTemplateParts ).some(
					( allowedTemplate ) =>
						currentContentId.startsWith( allowedTemplate ) &&
						allowedTemplates[ allowedTemplate ]
			  )
			: currentContentType === EditorViewContentType.WP_TEMPLATE_PART;
		const shouldBeAvailableOnPostEditor =
			currentContentType === EditorViewContentType.POST &&
			availableInPostEditor;
		const shouldBeAvailableOnPageEditor =
			currentContentType === EditorViewContentType.PAGE &&
			availableInPageEditor;

		return (
			shouldBeAvailableOnTemplate ||
			shouldBeAvailableOnTemplatePart ||
			shouldBeAvailableOnPostEditor ||
			shouldBeAvailableOnPageEditor
		);
	}

	/**
	 * Unregisters blocks before entering a restricted area based on the current template or page/post.
	 *
	 * This method iterates over all blocks with restrictions and unregisters them if they should not be registered
	 * based on the current content ID and whether the editor is in a post, page, template or template part. It uses a block registration
	 * strategy to unregister the blocks, which depends on whether the block is a variation block or a regular block.
	 *
	 * @param {Object}  params                    - The parameters for the method.
	 * @param {string}  params.currentContentId   - The ID of the current content.
	 * @param {boolean} params.currentContentType - The type of the current content.
	 */
	unregisterBlocksBeforeEnteringRestrictedArea( {
		currentContentId,
		currentContentType,
	}: {
		currentContentId: string;
		currentContentType: EditorViewContentType;
	} ) {
		for ( const blockWithRestrictionName of Object.keys(
			BLOCKS_WITH_RESTRICTION
		) ) {
			if (
				this.shouldBlockBeRegistered( {
					blockWithRestrictionName,
					currentContentId,
					currentContentType,
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
	 * This method is the main entry point for the block registration manager. It is called with a EditorViewChangeDetector object,
	 * and registers and unregisters blocks based on the current template and whether the editor is in a post or page.
	 *
	 * @param {EditorViewChangeDetector} EditorViewChangeDetector - The template change detector object.
	 */
	run( editorViewChangeDetector: EditorViewChangeDetector ) {
		this.registerBlocksAfterLeavingRestrictedArea();
		this.unregisterBlocksBeforeEnteringRestrictedArea( {
			currentContentId:
				editorViewChangeDetector.getCurrentContentId() || '',
			currentContentType:
				editorViewChangeDetector.getCurrentContentType(),
		} );
	}
}
