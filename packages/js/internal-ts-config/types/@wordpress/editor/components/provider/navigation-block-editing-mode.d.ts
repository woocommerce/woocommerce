declare module '@wordpress/editor/build-types/components/provider/navigation-block-editing-mode' {
	/**
	 * For the Navigation block editor, we need to force the block editor to contentOnly for that block.
	 *
	 * Set block editing mode to contentOnly when entering Navigation focus mode.
	 * this ensures that non-content controls on the block will be hidden and thus
	 * the user can focus on editing the Navigation Menu content only.
	 */
	export default function NavigationBlockEditingMode(): void;
}
