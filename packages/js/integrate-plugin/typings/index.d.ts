declare module '@wordpress/create-block/lib/cli-error' {
	export default Error;
}
declare module '@wordpress/create-block/lib/output' {
	function writeOutputTemplate(
		inputFile: string,
		outputFile: string,
		view: {
			plugin?: string;
			slug: string;
		}
	): Promise< void >;
	export { writeOutputTemplate };
}
