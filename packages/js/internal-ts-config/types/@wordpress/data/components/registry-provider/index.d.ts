/// <reference path="./context.d.ts" />
/// <reference path="./use-registry.d.ts" />

declare module '@wordpress/data/build-types/components/registry-provider' {
	export { default as useRegistry } from "@wordpress/data/build-types/components/registry-provider/use-registry";
	export { default as RegistryProvider, RegistryConsumer } from "@wordpress/data/build-types/components/registry-provider/context";
}
