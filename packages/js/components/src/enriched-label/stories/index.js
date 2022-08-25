/**
 * External dependencies
 */
import { EnrichedLabel } from '@woocommerce/components';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';

export const Basic = () => (
	<CheckboxControl
		label={
			<EnrichedLabel
				label="My label"
				helpDescription="My description."
				moreUrl="https://woocommerce.com"
				tooltipLinkCallback={ () => {
					// eslint-disable-next-line no-alert
					window.alert( 'Learn More clicked' );
				} }
			/>
		}
		onChange={ () => {} }
	/>
);

export default {
	title: 'WooCommerce Admin/components/EnrichedLabel',
	component: EnrichedLabel,
};
