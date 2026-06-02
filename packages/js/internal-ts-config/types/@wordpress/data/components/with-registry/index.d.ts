declare module '@wordpress/data/build-types/components/with-registry' {
	export default withRegistry;
	/**
	 * Higher-order component which renders the original component with the current
	 * registry context passed as its `registry` prop.
	 *
	 * @param {Component} OriginalComponent Original component.
	 *
	 * @return {Component} Enhanced component.
	 */
	declare const withRegistry: (Inner: import("react").ComponentType<any>) => (props: any) => import("react").JSX.Element;
}
