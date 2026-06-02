/// <reference path="./create-batch.d.ts" />
/// <reference path="./default-processor.d.ts" />

declare module '@wordpress/core-data/build-types/batch' {
	export { default as createBatch } from "@wordpress/core-data/build-types/batch/create-batch";
	export { default as defaultProcessor } from "@wordpress/core-data/build-types/batch/default-processor";
}
