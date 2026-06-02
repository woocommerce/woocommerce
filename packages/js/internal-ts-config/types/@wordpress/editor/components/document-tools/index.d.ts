declare module '@wordpress/editor/build-types/components/document-tools' {
	export default DocumentTools;
	declare function DocumentTools({ className, disableBlockTools }: {
	    className: any;
	    disableBlockTools?: boolean | undefined;
	}): import("react").JSX.Element;
}
