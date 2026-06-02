declare module '@wordpress/editor/build-types/store/utils/is-template-revertable' {
	/**
	 * Check if a template or template part is revertable to its original theme-provided file.
	 *
	 * @param {Object} templateOrTemplatePart The entity to check.
	 * @return {boolean} Whether the entity is revertable.
	 */
	export default function isTemplateRevertable(templateOrTemplatePart: Object): boolean;
}
