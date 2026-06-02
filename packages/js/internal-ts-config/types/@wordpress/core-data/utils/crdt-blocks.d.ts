/// <reference path="../types.d.ts" />

declare module '@wordpress/core-data/build-types/utils/crdt-blocks' {
	import { Y } from '@wordpress/sync';
	/**
	 * Internal dependencies
	 */
	import type { WPBlockSelection } from '@wordpress/core-data/build-types/types';
	interface BlockAttributes {
	    [key: string]: unknown;
	}
	export interface Block {
	    attributes: BlockAttributes;
	    clientId?: string;
	    innerBlocks: Block[];
	    originalContent?: string;
	    validationIssues?: string[];
	    name: string;
	}
	export type YBlock = Y.Map<string | string[] | YBlockAttributes | YBlocks>;
	export type YBlocks = Y.Array<YBlock>;
	export type YBlockAttributes = Y.Map<Y.Text | unknown>;
	/**
	 * Merge incoming block data into the local Y.Doc.
	 * This function is called to sync local block changes to a shared Y.Doc.
	 *
	 * @param yblocks        The blocks in the local Y.Doc.
	 * @param incomingBlocks Gutenberg blocks being synced, either from a peer or from the local editor.
	 * @param lastSelection  The last cursor position, used for hinting the diff algorithm.
	 */
	export declare function mergeCrdtBlocks(yblocks: YBlocks, incomingBlocks: Block[], lastSelection: WPBlockSelection | null): void;
	export {};
}
