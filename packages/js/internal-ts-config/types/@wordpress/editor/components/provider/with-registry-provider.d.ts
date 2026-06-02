declare module '@wordpress/editor/build-types/components/provider/with-registry-provider' {
	export default withRegistryProvider;
	declare const withRegistryProvider: (Inner: import("react").ComponentType<any>) => ({ useSubRegistry, ...props }: any) => import("react").JSX.Element;
}
