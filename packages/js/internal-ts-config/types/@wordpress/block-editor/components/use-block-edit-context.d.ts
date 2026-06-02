declare module '@wordpress/block-editor/components/use-block-edit-context' {
	export function useBlockEditContext(): { name: string; isSelected?: boolean; clientId: string; layout: unknown };
}
