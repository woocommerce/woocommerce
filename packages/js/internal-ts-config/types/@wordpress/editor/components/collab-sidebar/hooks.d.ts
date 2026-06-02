/// <reference path="./utils.d.ts" />

declare module '@wordpress/editor/build-types/components/collab-sidebar/hooks' {
	export function useBlockComments(postId: any): {
	    resultComments: any[];
	    unresolvedSortedThreads: any[];
	    reflowComments: import("react").DispatchWithoutAction;
	    commentLastUpdated: number;
	};
	export function useBlockCommentsActions(reflowComments?: typeof noop): {
	    onCreate: ({ content, parent }: {
	        content: any;
	        parent: any;
	    }) => Promise<any>;
	    onEdit: ({ id, content, status }: {
	        id: any;
	        content: any;
	        status: any;
	    }) => Promise<void>;
	    onDelete: (comment: any) => Promise<void>;
	};
	export function useEnableFloatingSidebar(enabled?: boolean): void;
	export function useFloatingThread({ thread, calculatedOffset, setHeights, selectedThread, setBlockRef, commentLastUpdated, }: {
	    thread: any;
	    calculatedOffset: any;
	    setHeights: any;
	    selectedThread: any;
	    setBlockRef: any;
	    commentLastUpdated: any;
	}): {
	    blockRef: import("react").MutableRefObject<undefined>;
	    y: number;
	    refs: {
	        reference: import("react").MutableRefObject<import("@floating-ui/react-dom").ReferenceType | null>;
	        floating: import("react").MutableRefObject<HTMLElement | null>;
	        setReference: (node: import("@floating-ui/react-dom").ReferenceType | null) => void;
	        setFloating: (node: HTMLElement | null) => void;
	    };
	};
	import { noop } from '@wordpress/editor/build-types/components/collab-sidebar/utils';
}
