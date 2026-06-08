// Compatibility shim: the real module moved to tests/e2e-pw/utils/blocks
// during the QAO-185 e2e merge. Kept as CommonJS because
// bin/generate-test-translations.js require()s it via plain node; an ESM
// re-export would throw. Removed in QAO-407 (#6) with the rest of this tree.
module.exports = require( '../../../../../tests/e2e-pw/utils/blocks/get-test-translation.js' );
