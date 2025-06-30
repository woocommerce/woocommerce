/**
 * External dependencies
 */
import { MenuItem as DropdownMenuItem } from '@wordpress/components';
import { MediaUpload } from '@wordpress/media-utils';

export type MediaLibraryMenuItemProps = Omit<
	MediaUpload.Props< boolean >,
	'render' | 'onChange'
> &
	React.ComponentProps< typeof DropdownMenuItem > & {
		text?: string;
	};
