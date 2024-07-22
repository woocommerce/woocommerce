/**
 * External dependencies
 */
import { MenuItem as DropdownMenuItem } from '@wordpress/components';
import { MediaUpload } from '@wordpress/media-utils';

export type MediaLibraryMenuItemProps = Omit<
	MediaUpload.Props< boolean >,
	'children' | 'render' | 'onChange'
> &
	Pick< DropdownMenuItem.Props, 'icon' | 'iconPosition' | 'text' | 'info' >;
