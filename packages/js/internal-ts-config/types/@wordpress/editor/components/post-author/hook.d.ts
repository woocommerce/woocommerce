declare module '@wordpress/editor/build-types/components/post-author/hook' {
	export function useAuthorsQuery(search: any): {
	    authorId: any;
	    authorOptions: any[];
	    postAuthor: import("@wordpress/core-data").User<"edit"> | undefined;
	    isLoading: any;
	};
}
