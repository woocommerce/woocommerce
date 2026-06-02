declare module '@wordpress/editor/build-types/components/keyboard-shortcut-help-modal/shortcut' {
	export default Shortcut;
	declare function Shortcut({ description, keyCombination, aliases, ariaLabel }: {
	    description: any;
	    keyCombination: any;
	    aliases?: never[] | undefined;
	    ariaLabel: any;
	}): import("react").JSX.Element;
}
