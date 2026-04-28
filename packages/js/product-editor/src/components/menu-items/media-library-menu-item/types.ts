/**
 * External dependencies
 */
import { MenuItem as DropdownMenuItem } from '@wordpress/components';

/**
 * Props for MediaUpload component.
 * Defined locally because the native @wordpress/media-utils
 * no longer exports a Props namespace on MediaUpload.
 */
interface MediaUploadBaseProps {
	allowedTypes?: string[];
	gallery?: boolean;
	multiple?: boolean | 'add';
	title?: string;
	modalClass?: string;
	value?: number | number[];
	onSelect?: ( value: unknown ) => void;
}

export type MediaLibraryMenuItemProps = Omit<
	MediaUploadBaseProps,
	'render' | 'onChange'
> &
	Omit< React.ComponentProps< typeof DropdownMenuItem >, 'onSelect' > & {
		text?: string;
	};
