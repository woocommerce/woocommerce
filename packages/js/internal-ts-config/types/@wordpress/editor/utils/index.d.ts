/// <reference path="./get-template-part-icon.d.ts" />
/// <reference path="./media-upload/index.d.ts" />

declare module '@wordpress/editor/build-types/utils' {
	export { mediaUpload };
	export { cleanForSlug } from "./url.js";
	export { getTemplatePartIcon } from "@wordpress/editor/build-types/utils/get-template-part-icon";
	import mediaUpload from '@wordpress/editor/build-types/utils/media-upload';
}
