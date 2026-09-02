// The prefix pairs with `--input=../../src` in package.json's build:docs: file
// paths are relative to plugins/woocommerce/src, links live in
// docs/third-party-developers/extensibility/hooks/. Keep the two in sync.
const files = ( sources ) => {
	return sources && sources.length
		? {
				ul: sources.map( ( file ) => {
					return `[${ file }](../../../../../../src/${ file })`;
				} ),
		  }
		: null;
};

module.exports = { files };
