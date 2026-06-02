declare module '@wordpress/block-editor/components/block-toolbar' {
	import { ComponentType } from "react";

	declare namespace BlockToolbar {
	    interface Props {
	        children?: never | undefined;
	    }
	}
	declare const BlockToolbar: ComponentType<BlockToolbar.Props>;

	export default BlockToolbar;
}
