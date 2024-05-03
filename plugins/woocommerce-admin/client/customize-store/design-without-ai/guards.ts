// TODO: This is a quick fix to avoid crash when the font library is not installed. Check: https://github.com/woocommerce/woocommerce/issues/44315
export const hasFontLibraryInstalled = () => {
	return false;
};
