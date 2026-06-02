/// <reference path="./context.d.ts" />
/// <reference path="./use-async-mode.d.ts" />

declare module '@wordpress/data/build-types/components/async-mode-provider' {
	export { default as useAsyncMode } from "@wordpress/data/build-types/components/async-mode-provider/use-async-mode";
	export { default as AsyncModeProvider, AsyncModeConsumer } from "@wordpress/data/build-types/components/async-mode-provider/context";
}
