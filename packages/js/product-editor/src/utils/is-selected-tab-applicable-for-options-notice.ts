/**
 * Check if the tab is applicable for options notice.
 *
 * @param {string|null|undefined} tab - Name of the selected tab.
 * @return {boolean} True if the tab is applicable for options notice.
 */
export const isSelectedTabApplicableForOptionsNotice = (
	tab: string | null | undefined
) => {
	return [ 'inventory', 'pricing', 'shipping' ].includes( tab || '' );
};
