declare module '@wordpress/block-editor/components/block-selection-clearer' {
	import { ComponentType, HTMLProps } from "react";

	declare namespace BlockSelectionClearer {
	    type Props = HTMLProps<HTMLDivElement>;
	}
	declare const BlockSelectionClearer: ComponentType<BlockSelectionClearer.Props>;

	export default BlockSelectionClearer;
}
