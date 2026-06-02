declare module '@wordpress/editor/build-types/components/post-excerpt/panel' {
	/**
	 * Is rendered if the post type supports excerpts and allows editing the excerpt.
	 *
	 * @return {React.ReactNode} The rendered PostExcerptPanel component.
	 */
	export default function PostExcerptPanel(): React.ReactNode;
	export function PrivatePostExcerptPanel(): import("react").JSX.Element;
}
