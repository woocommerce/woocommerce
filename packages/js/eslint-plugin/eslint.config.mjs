/**
 * Internal dependencies
 */
import woocommerce from './index.js';

/*
 * This package is the base that @woocommerce/eslint-config consumes, so it lints
 * itself with its own recommended config rather than the private layer -
 * depending on that layer would be a dependency cycle.
 */
export default [ ...woocommerce.configs.recommended ];
