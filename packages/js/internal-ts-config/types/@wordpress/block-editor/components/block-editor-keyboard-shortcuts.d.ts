declare module '@wordpress/block-editor/components/block-editor-keyboard-shortcuts' {
	import { ComponentType } from "react";

	declare namespace BlockEditorKeyboardShortcuts {
	    interface Props {
	        children?: never | undefined;
	    }
	}
	declare const BlockEditorKeyboardShortcuts: ComponentType<BlockEditorKeyboardShortcuts.Props>;

	export default BlockEditorKeyboardShortcuts;
}
