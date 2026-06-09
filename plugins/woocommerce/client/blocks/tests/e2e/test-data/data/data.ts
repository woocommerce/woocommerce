// Compatibility shim: the real test data moved to tests/e2e-pw/test-data/blocks
// during the QAO-185 e2e merge. Kept as CommonJS (explicit .ts) because
// bin/generate-test-translations.js require()s it via plain node; an ESM
// re-export would throw. Removed in QAO-407 (#6) with the rest of this tree.
module.exports = require( '../../../../../../tests/e2e-pw/test-data/blocks/data/data.ts' );
