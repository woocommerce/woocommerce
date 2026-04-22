/**
 * External dependencies
 */
import type { Attachment } from '@wordpress/media-utils';

export type InsertUrlLinkErrorCallback = ( error: string ) => void;

export type InsertUrlMenuItemProps = {
	onLinkSuccess( files: Attachment[] ): void;
	onLinkError: InsertUrlLinkErrorCallback;
};
