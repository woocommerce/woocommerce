/**
 * Internal dependencies
 */
import ErrorPlaceholder from '../';

export default {
	title: 'WooCommerce Blocks/components/ErrorPlaceholder',
	component: ErrorPlaceholder,
};

export const Default = () => (
	<ErrorPlaceholder
		error={ {
			message:
				'Unfortunately, we seem to have encountered a slight problem',
			type: 'general',
		} }
	/>
);
