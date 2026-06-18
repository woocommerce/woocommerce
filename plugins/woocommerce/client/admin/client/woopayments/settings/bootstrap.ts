export type WooPaymentsSettingsBootstrap = Record< string, unknown >;

type WooPaymentsSettingsWindow = typeof window & {
	wcSettings?: {
		admin?: {
			woopaymentsSettings?: WooPaymentsSettingsBootstrap;
		};
	};
};

export const getWooPaymentsSettingsBootstrap =
	(): WooPaymentsSettingsBootstrap => {
		const settingsWindow = window as WooPaymentsSettingsWindow;

		return settingsWindow.wcSettings?.admin?.woopaymentsSettings ?? {};
	};
