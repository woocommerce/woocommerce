declare module '@wordpress/editor/build-types/components/post-format' {
	/**
	 * `PostFormat` a component that allows changing the post format while also providing a suggestion for the current post.
	 *
	 * @example
	 * ```jsx
	 * <PostFormat />
	 * ```
	 *
	 * @return {React.ReactNode} The rendered PostFormat component.
	 */
	export default function PostFormat(): React.ReactNode;
	export const POST_FORMATS: ({
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Aside">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Audio">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Chat">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Gallery">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Image">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Link">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Quote">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Standard">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Status">;
	} | {
	    id: string;
	    caption: import("@wordpress/i18n").TranslatableText<"Video">;
	})[];
}
