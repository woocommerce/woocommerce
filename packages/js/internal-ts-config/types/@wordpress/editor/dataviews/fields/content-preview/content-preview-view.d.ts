declare module '@wordpress/editor/build-types/dataviews/fields/content-preview/content-preview-view' {
	import type { BasePost } from '@wordpress/fields';
	export default function PostPreviewView({ item }: {
	    item: BasePost;
	}): import("react").JSX.Element;
}
