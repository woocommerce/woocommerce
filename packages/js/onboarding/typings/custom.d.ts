// SVG assets are resolved to a URL string by the consuming bundler
// (e.g. webpack `type: 'asset'`) and copied verbatim by the package build.
declare module '*.svg' {
	const url: string;
	export default url;
}
