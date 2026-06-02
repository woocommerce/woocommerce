declare module '@wordpress/editor/build-types/components/sidebar' {
	export default Sidebar;
	declare function Sidebar({ extraPanels, onActionPerformed }: {
	    extraPanels: any;
	    onActionPerformed: any;
	}): import("react").JSX.Element;
}
