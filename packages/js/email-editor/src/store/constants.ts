export const storeName = 'email-editor/editor';

export const mainSidebarId = 'email-editor/editor/main';
export const mainSidebarDocumentTab = 'document';
export const mainSidebarBlockTab = 'block';
export const stylesSidebarId = 'email-editor/editor/styles';

// these values are set once on a page load, so it's fine to keep them here.
export const editorCurrentPostType =
	window.WooCommerceEmailEditor.current_post_type;
export const editorCurrentPostId = parseInt(
	window.WooCommerceEmailEditor.current_post_id,
	10
);
