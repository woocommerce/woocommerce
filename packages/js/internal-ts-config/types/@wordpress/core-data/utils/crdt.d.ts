/// <reference path="../entity-types/index.d.ts" />
/// <reference path="../entity-types/post.d.ts" />
/// <reference path="../types.d.ts" />
/// <reference path="./crdt-blocks.d.ts" />

declare module '@wordpress/core-data/build-types/utils/crdt' {
	/**
	 * WordPress dependencies
	 */
	import { type CRDTDoc, type ObjectData } from '@wordpress/sync';
	/**
	 * Internal dependencies
	 */
	import { type Block } from '@wordpress/core-data/build-types/utils/crdt-blocks';
	import { type Post } from '@wordpress/core-data/build-types/entity-types/post';
	import { type Type } from '@wordpress/core-data/build-types/entity-types';
	import type { WPSelection } from '@wordpress/core-data/build-types/types';
	export type PostChanges = Partial<Post> & {
	    blocks?: Block[];
	    excerpt?: Post['excerpt'] | string;
	    selection?: WPSelection;
	    title?: Post['title'] | string;
	};
	/**
	 * Given a set of local changes to a generic entity record, apply those changes
	 * to the local Y.Doc.
	 *
	 * @param {CRDTDoc}               ydoc
	 * @param {Partial< ObjectData >} changes
	 * @return {void}
	 */
	export declare function defaultApplyChangesToCRDTDoc(ydoc: CRDTDoc, changes: ObjectData): void;
	/**
	 * Given a set of local changes to a post record, apply those changes to the
	 * local Y.Doc.
	 *
	 * @param {CRDTDoc}     ydoc
	 * @param {PostChanges} changes
	 * @param {Type}        postType
	 * @return {void}
	 */
	export declare function applyPostChangesToCRDTDoc(ydoc: CRDTDoc, changes: PostChanges, postType: Type): void;
	export declare function defaultGetChangesFromCRDTDoc(crdtDoc: CRDTDoc): ObjectData;
	/**
	 * Given a local Y.Doc that *may* contain changes from remote peers, compare
	 * against the local record and determine if there are changes (edits) we want
	 * to dispatch.
	 *
	 * @param {CRDTDoc} ydoc
	 * @param {Post}    editedRecord
	 * @param {Type}    postType
	 * @return {Partial<PostChanges>} The changes that should be applied to the local record.
	 */
	export declare function getPostChangesFromCRDTDoc(ydoc: CRDTDoc, editedRecord: Post, postType: Type): PostChanges;
}
