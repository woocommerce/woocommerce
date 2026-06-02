declare module '@wordpress/editor/build-types/store/local-autosave' {
	export function localAutosaveGet(postId: any, isPostNew: any): string | null;
	export function localAutosaveSet(postId: any, isPostNew: any, title: any, content: any, excerpt: any): void;
	export function localAutosaveClear(postId: any, isPostNew: any): void;
}
