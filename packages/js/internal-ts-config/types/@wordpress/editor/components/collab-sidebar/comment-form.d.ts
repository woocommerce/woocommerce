/// <reference path="./utils.d.ts" />

declare module '@wordpress/editor/build-types/components/collab-sidebar/comment-form' {
	export default CommentForm;
	declare function CommentForm({ onSubmit, onCancel, thread, submitButtonText, labelText, reflowComments, }: {
	    onSubmit: any;
	    onCancel: any;
	    thread: any;
	    submitButtonText: any;
	    labelText: any;
	    reflowComments?: typeof noop | undefined;
	}): import("react").JSX.Element;
	import { noop } from '@wordpress/editor/build-types/components/collab-sidebar/utils';
}
