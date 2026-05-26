/**
 * External dependencies
 */
import { BlockInstance, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';

const LEGACY_ATTRIBUTE_OPTIONS_BLOCK =
	'woocommerce/add-to-cart-with-options-variation-selector-attribute-options';
const INNER_CHIPS = 'woocommerce/product-filter-chips';
const INNER_DROPDOWN = 'woocommerce/dropdown';

interface MigratedAttributeSettings {
	displayStyle: string;
	autoselect: boolean;
	disabledAttributesAction: 'disable' | 'hide';
}

function containsLegacyAttributeOptionsBlock(
	blocks: BlockInstance[]
): boolean {
	return blocks.some( ( block ) => {
		if (
			block.name === 'core/missing' &&
			block.attributes.originalName === LEGACY_ATTRIBUTE_OPTIONS_BLOCK
		) {
			return true;
		}

		if ( block.innerBlocks?.length ) {
			return containsLegacyAttributeOptionsBlock( block.innerBlocks );
		}

		return false;
	} );
}

function migrateInnerBlocks(
	innerBlocks: BlockInstance[],
	settings: MigratedAttributeSettings
): BlockInstance[] {
	return innerBlocks.flatMap( ( block ) => {
		if (
			block.name === 'core/missing' &&
			block.attributes.originalName === LEGACY_ATTRIBUTE_OPTIONS_BLOCK
		) {
			if ( block.originalContent?.includes( '"autoselect":true' ) ) {
				settings.autoselect = true;
			}
			if (
				block.originalContent?.includes(
					'"disabledAttributesAction":"hide"'
				)
			) {
				settings.disabledAttributesAction = 'hide';
			}
			if (
				block.originalContent?.includes( '"optionStyle":"dropdown"' )
			) {
				settings.displayStyle = INNER_DROPDOWN;
				return [ createBlock( INNER_DROPDOWN ) ];
			}
			settings.displayStyle = INNER_CHIPS;
			return [ createBlock( INNER_CHIPS ) ];
		}

		if ( block.innerBlocks?.length ) {
			return [
				{
					...block,
					innerBlocks: migrateInnerBlocks(
						block.innerBlocks,
						settings
					),
				},
			];
		}

		return [ block ];
	} );
}

const deprecated = [
	{
		save,
		isEligible(
			_attributes: Record< string, unknown >,
			innerBlocks: BlockInstance[]
		) {
			return containsLegacyAttributeOptionsBlock( innerBlocks );
		},
		migrate(
			attributes: Record< string, unknown >,
			innerBlocks: BlockInstance[]
		) {
			const settings: MigratedAttributeSettings = {
				displayStyle: metadata.attributes.displayStyle.default,
				autoselect: metadata.attributes.autoselect.default,
				disabledAttributesAction: metadata.attributes
					.disabledAttributesAction.default as 'disable' | 'hide',
			};

			const migratedInnerBlocks = migrateInnerBlocks(
				innerBlocks,
				settings
			);

			return [
				{
					...attributes,
					displayStyle: settings.displayStyle,
					autoselect: settings.autoselect,
					disabledAttributesAction: settings.disabledAttributesAction,
				},
				migratedInnerBlocks,
			];
		},
	},
];

export default deprecated;
