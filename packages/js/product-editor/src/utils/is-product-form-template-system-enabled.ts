export default function isProductFormTemplateSystemEnabled() {
	return !! window.wcAdminFeatures?.[ 'product-editor-template-system' ];
}
