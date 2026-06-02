declare module '@wordpress/editor/build-types/components/editor' {
	export default Editor;
	declare function Editor({ postType, postId, templateId, settings, children, initialEdits, onActionPerformed, extraContent, extraSidebarPanels, ...props }: {
	    [x: string]: any;
	    postType: any;
	    postId: any;
	    templateId: any;
	    settings: any;
	    children: any;
	    initialEdits: any;
	    onActionPerformed: any;
	    extraContent: any;
	    extraSidebarPanels: any;
	}): import("react").JSX.Element;
}
