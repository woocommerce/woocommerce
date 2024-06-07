/**
 * External dependencies
 */
import {
	BlockConfiguration,
	registerBlockType,
	unregisterBlockType,
	registerBlockVariation,
	unregisterBlockVariation,
	BlockVariation,
	BlockAttributes,
} from '@wordpress/blocks';

export interface BlockRegistrationStrategy {
	register(
		blockNameOrMetadata: string | Partial< BlockConfiguration >,
		blockSettings: Partial< BlockConfiguration >
	): boolean;
	unregister( blockName: string, variationName?: string ): boolean;
}

export class BlockTypeStrategy implements BlockRegistrationStrategy {
	register(
		blockNameOrMetadata: string | Partial< BlockConfiguration >,
		blockSettings: Partial< BlockConfiguration >
	): boolean {
		return Boolean(
			// @ts-expect-error: `registerBlockType` is typed in WordPress core
			registerBlockType( blockNameOrMetadata, blockSettings )
		);
	}

	unregister( blockName: string ): boolean {
		return Boolean( unregisterBlockType( blockName ) );
	}
}

// Strategy for BlockVariation
export class BlockVariationStrategy implements BlockRegistrationStrategy {
	register(
		blockName: string,
		blockSettings: Partial< BlockConfiguration >
	): boolean {
		return Boolean(
			registerBlockVariation(
				blockName,
				blockSettings as BlockVariation< BlockAttributes >
			)
		);
	}

	unregister( blockName: string, variationName: string ): boolean {
		return Boolean( unregisterBlockVariation( blockName, variationName ) );
	}
}
