declare global {
	interface Window {
		wcCalypsoBridge?: {
			isWooExpress: boolean;
		};
	}
}

export const isWooExpress = (): boolean =>
	window.wcCalypsoBridge !== undefined && window.wcCalypsoBridge.isWooExpress;
