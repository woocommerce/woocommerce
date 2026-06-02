declare module '@wordpress/editor/build-types/components/post-actions/actions' {
	export function usePostActions({ postType, onActionPerformed, context }: {
	    postType: any;
	    onActionPerformed: any;
	    context: any;
	}): any[];
}
