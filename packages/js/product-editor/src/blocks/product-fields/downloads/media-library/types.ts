/**
 * External dependencies
 */
import type { Attachment } from '@wordpress/media-utils';

export type ChildrenProps = {
	open(): void;
};

export type MediaLibraryProps = {
	allowedTypes?: string[];
	modalTitle?: string;
	modalButtonText?: string;
	multiple?: boolean | 'add';
	className?: string;
	uploaderParams?: Record< string, string >;
	children( props: ChildrenProps ): JSX.Element;
	onSelect( selection: Attachment[] ): void;
};
