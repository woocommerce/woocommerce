declare module '@wordpress/editor/build-types/components/visual-editor/use-select-nearest-editable-block' {
	export default function useSelectNearestEditableBlock({ isEnabled, }?: {
	    isEnabled?: boolean | undefined;
	}): import("react").RefCallback<Node | null>;
}
