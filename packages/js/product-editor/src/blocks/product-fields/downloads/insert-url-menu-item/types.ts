/**
 * External dependencies
 */
import { MediaItem } from '@wordpress/media-utils';

export type InsertUrlLinkErrorCallback = ( error: string ) => void;

export type InsertUrlMenuItemProps = {
	onLinkSuccess( files: MediaItem[] ): void;
	onLinkError: InsertUrlLinkErrorCallback;
};
