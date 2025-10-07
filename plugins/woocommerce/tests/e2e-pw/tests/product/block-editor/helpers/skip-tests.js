/**
 * Internal dependencies
 */
import { test } from '../../../../fixtures/block-editor-fixtures';

// eslint-disable-next-line jest/no-export
export const skipTestsForDeprecatedFeature = () =>
	test.skip(
		() => true,
		'Experimental block-based product editor is officially deprecated since 10.2. See: https://developer.woocommerce.com/2025/07/23/10-1-pre-release-updates/#:~:text=%F0%9F%8C%85%20Say%20sayonara,the%20near%20future'
	);
