declare module '@wordpress/editor/build-types/components/post-actions/set-as-posts-page' {
	export function useSetAsPostsPageAction(): {
	    id: string;
	    label: import("@wordpress/i18n").TranslatableText<"Set as posts page">;
	    isEligible(post: any): boolean;
	    modalFocusOnMount: string;
	    RenderModal: ({ items, closeModal }: {
	        items: any;
	        closeModal: any;
	    }) => import("react").JSX.Element;
	};
}
