/**
 * client-zip is meant to be used in a browser and is therefore released as an
 * ES module only. Jest can resolve @wordpress/editor from the WordPress
 * compatibility cache, so map every client-zip resolution to this CommonJS
 * mock instead of relying on a setup-file mock tied to one node_modules tree.
 *
 * See: https://github.com/Touffy/client-zip/issues/28
 */
module.exports = {
	downloadZip: jest.fn(),
};
