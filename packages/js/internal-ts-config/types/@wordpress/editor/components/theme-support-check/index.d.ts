declare module '@wordpress/editor/build-types/components/theme-support-check' {
	/**
	 * Checks if the current theme supports specific features and renders the children if supported.
	 *
	 * @param {Object}          props             The component props.
	 * @param {React.ReactNode} props.children    The children to render if the theme supports the specified features.
	 * @param {string|string[]} props.supportKeys The key(s) of the theme support(s) to check.
	 *
	 * @return {React.ReactNode} The rendered children if the theme supports the specified features, otherwise null.
	 */
	export default function ThemeSupportCheck({ children, supportKeys }: {
	    children: React.ReactNode;
	    supportKeys: string | string[];
	}): React.ReactNode;
}
