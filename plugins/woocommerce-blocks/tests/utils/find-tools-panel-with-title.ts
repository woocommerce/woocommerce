export const findToolsPanelWithTitle = async ( panelTitle: string ) => {
	const panelToggleSelector = `//div[contains(@class, "components-tools-panel-header")]//h2[contains(@class, "components-heading") and contains(text(),"${ panelTitle }")]`;
	const panelSelector = `${ panelToggleSelector }/ancestor::*[contains(concat(" ", @class, " "), " components-tools-panel ")]`;
	return await page.waitForXPath( panelSelector );
};
