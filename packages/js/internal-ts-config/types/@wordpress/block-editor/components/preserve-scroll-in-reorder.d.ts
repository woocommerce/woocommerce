declare module '@wordpress/block-editor/components/preserve-scroll-in-reorder' {
	import { ComponentType } from "react";

	declare namespace PreserveScrollInReorder {
	    interface Props {
	        children?: never | undefined;
	    }
	}
	declare const PreserveScrollInReorder: ComponentType<PreserveScrollInReorder.Props>;

	export default PreserveScrollInReorder;
}
