export function setShowNewNavigation( newNavigation: boolean ) {
	window.__productNewNavigation = newNavigation;
}

export function getShowNewNavigation(): boolean {
	return window.__productNewNavigation ?? false;
}
