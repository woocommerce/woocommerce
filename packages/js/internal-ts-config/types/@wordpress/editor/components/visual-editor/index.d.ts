declare module '@wordpress/editor/build-types/components/visual-editor' {
	export default VisualEditor;
	declare function VisualEditor({ autoFocus, styles, disableIframe, iframeProps, contentRef, className, }: {
	    autoFocus: any;
	    styles: any;
	    disableIframe?: boolean | undefined;
	    iframeProps: any;
	    contentRef: any;
	    className: any;
	}): import("react").JSX.Element;
}
