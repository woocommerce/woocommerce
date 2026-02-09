export type Route = {
	/**
	 * The page id.
	 */
	key: string;
	areas: {
		/**
		 * The sidebar component.
		 */
		sidebar: React.ReactNode;
		/**
		 * The content component.
		 */
		content?: React.ReactNode;
		/**
		 * The edit component.
		 */
		edit?: React.ReactNode;
		/**
		 * The mobile component.
		 */
		mobile?: React.ReactNode;
		/**
		 * Whether the page can be previewed.
		 */
		preview?: boolean;
	};
	widths?: {
		/**
		 * The sidebar width.
		 */
		sidebar?: number;
		/**
		 * The main content width.
		 */
		content?: number;
		/**
		 * The edit component width.
		 */
		edit?: number;
	};
};

export type Location = {
	pathname: string;
	search: string;
	hash: string;
	state: null;
	key: string;
	params: Record< string, string >;
};

export type DataFormItem = Record< string, BaseSettingsField[ 'value' ] >;
