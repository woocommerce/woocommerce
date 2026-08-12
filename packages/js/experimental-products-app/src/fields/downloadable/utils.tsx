/**
 * External dependencies
 */
import { Icon } from '@wordpress/ui';
import { file as FileIcon } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import { ProductEntityRecord } from '../types';

export type UploadedItem = Omit<
	ProductEntityRecord[ 'downloads' ][ 0 ],
	'id'
> & {
	id?: string;
};

export type ItemToUpload = UploadedItem & { type: string };

export const getLastPathFromUrl = ( url: string ) => {
	try {
		const urlObj = new URL( url );
		return urlObj.pathname.split( '/' ).pop() ?? url;
	} catch {
		const parts = url.split( '/' );
		return parts[ parts.length - 1 ];
	}
};

export const GenericThumbnail = () => (
	<Icon style={ { width: '100%', height: '100%' } } icon={ FileIcon } />
);

export const isImageFromLink = ( link: string ) => {
	const normalizedLink = link.toLowerCase();
	return [ '.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp', '.svg' ].some(
		( extension ) => normalizedLink.endsWith( extension )
	);
};

export const isItemToUpload = (
	download: UploadedItem | ItemToUpload
): download is ItemToUpload => {
	return 'type' in download;
};

export const isUploadedItem = (
	download: UploadedItem | ItemToUpload
): download is UploadedItem => {
	return ! ( 'type' in download );
};
