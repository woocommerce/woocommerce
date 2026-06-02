declare module '@wordpress/block-editor/components/skip-to-selected-block' {
	import { ComponentType } from "react";

	declare namespace SkipToSelectedBlock {
	    interface Props {
	        children?: never | undefined;
	    }
	}
	declare const SkipToSelectedBlock: ComponentType<SkipToSelectedBlock.Props>;

	export default SkipToSelectedBlock;
}
