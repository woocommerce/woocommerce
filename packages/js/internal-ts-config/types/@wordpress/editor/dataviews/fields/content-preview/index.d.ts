declare module '@wordpress/editor/build-types/dataviews/fields/content-preview' {
	/**
	 * WordPress dependencies
	 */
	import type { Field } from '@wordpress/dataviews';
	import type { BasePost } from '@wordpress/fields';
	declare const postPreviewField: Field<BasePost>;
	export default postPreviewField;
}
