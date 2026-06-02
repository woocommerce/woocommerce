/// <reference path="./utils.d.ts" />

declare module '@wordpress/editor/build-types/components/collab-sidebar/add-comment' {
	export function AddComment({ onSubmit, newNoteFormState, setNewNoteFormState, commentSidebarRef, reflowComments, isFloating, y, refs, }: {
	    onSubmit: any;
	    newNoteFormState: any;
	    setNewNoteFormState: any;
	    commentSidebarRef: any;
	    reflowComments?: typeof noop | undefined;
	    isFloating?: boolean | undefined;
	    y: any;
	    refs: any;
	}): import("react").JSX.Element | null;
	import { noop } from '@wordpress/editor/build-types/components/collab-sidebar/utils';
}
