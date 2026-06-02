declare module '@wordpress/editor/build-types/components/header/back-button' {
	export function useHasBackButton(): boolean;
	export default BackButton;
	declare const BackButton: {
	    (props: Omit<import("@wordpress/components/build-types/slot-fill/types").FillComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	};
}
