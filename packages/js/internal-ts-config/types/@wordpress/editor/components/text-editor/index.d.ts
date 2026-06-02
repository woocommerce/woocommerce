declare module '@wordpress/editor/build-types/components/text-editor' {
	export default function TextEditor({ autoFocus }: {
	    autoFocus?: boolean | undefined;
	}): import("react").JSX.Element;
}
