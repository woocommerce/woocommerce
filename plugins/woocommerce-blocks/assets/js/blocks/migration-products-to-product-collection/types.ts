/**
 * External dependencies
 */
import { type BlockInstance } from '@wordpress/blocks';

export type GetBlocksClientIds = ( blocks: BlockInstance[] ) => string[];
export type IsBlockType = ( block: BlockInstance ) => boolean;
export type TransformBlock = (
	block: BlockInstance,
	innerBlock: BlockInstance[]
) => BlockInstance;
export type ProductGridLayoutTypes = 'flex' | 'list';
export type PostTemplateLayoutTypes = 'grid' | 'default';

export type ProductGridLayout = {
	type: ProductGridLayoutTypes;
	columns: number;
};

export type PostTemplateLayout = {
	type: PostTemplateLayoutTypes;
	columnCount: number;
};
export type UpgradeNoticeStatuses = 'notseen' | 'seen' | 'reverted';
export type UpgradeNoticeStatus = {
	status: UpgradeNoticeStatuses;
	time: number;
	displayCount?: number;
};
