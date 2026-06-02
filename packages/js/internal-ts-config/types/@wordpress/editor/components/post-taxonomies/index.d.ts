declare module '@wordpress/editor/build-types/components/post-taxonomies' {
	export function PostTaxonomies({ taxonomyWrapper }: {
	    taxonomyWrapper?: ((x: any) => any) | undefined;
	}): import("react").JSX.Element[];
	export default PostTaxonomies;
}
