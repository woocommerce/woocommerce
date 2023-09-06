/**
 * External dependencies
 */
import { ProgressBar } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

export const Basic = () => (
	<div style={ { background: '#fff', height: '200px', padding: '20px' } }>
		<ProgressBar percent={ 20 } bgcolor={ '#eeeeee' } color={ '#007cba' } />
	</div>
);

export default {
	title: 'WooCommerce Admin/components/ProgressBar',
	component: ProgressBar,
};
