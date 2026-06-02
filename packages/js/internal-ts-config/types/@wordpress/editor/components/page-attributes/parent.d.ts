declare module '@wordpress/editor/build-types/components/page-attributes/parent' {
	/**
	 * Renders the Page Attributes Parent component. A dropdown menu in an editor interface
	 * for selecting the parent page of a given page.
	 *
	 * @return {React.ReactNode} The component to be rendered. Return null if post type is not hierarchical.
	 */
	export function PageAttributesParent(): React.ReactNode;
	export function ParentRow(): import("react").JSX.Element;
	export function getItemPriority(name: any, searchValue: any): number;
	export default PageAttributesParent;
}
