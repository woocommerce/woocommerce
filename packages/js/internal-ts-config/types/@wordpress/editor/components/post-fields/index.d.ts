declare module '@wordpress/editor/build-types/components/post-fields' {
	import type { Field } from '@wordpress/dataviews';
	import type { BasePostWithEmbeddedAuthor } from '@wordpress/fields';
	declare function usePostFields({ postType, }: {
	    postType: string;
	}): Field<BasePostWithEmbeddedAuthor>[];
	/**
	 * Hook to get the fields for a post (BasePost or BasePostWithEmbeddedAuthor).
	 */
	export default usePostFields;
}
