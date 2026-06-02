declare module '@wordpress/editor/build-types/components/post-taxonomies/check' {
	/**
	 * Renders the children components only if the current post type has taxonomies.
	 *
	 * @param {Object}          props          The component props.
	 * @param {React.ReactNode} props.children The children components to render.
	 *
	 * @return {React.ReactNode} The rendered children components or null if the current post type has no taxonomies.
	 */
	export default function PostTaxonomiesCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
