/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EnrichedLabel } from '../';
import './style.scss';

export default {
	title: 'WooCommerce Admin/components/EnrichedLabel',
	component: EnrichedLabel,
	argTypes: {
		tooltipLinkCallback: { action: 'tooltipLinkCallback' },
	},
};

const Template = ( args ) => (
	<EnrichedLabel
		label="My label"
		helpDescription="My description."
		moreUrl="https://woocommerce.com"
		tooltipLinkCallback={ () => {
			// eslint-disable-next-line no-alert
			window.alert( 'Learn More clicked' );
		} }
		{ ...args }
	/>
);

export const Basic = Template.bind( {} );
Basic.decorators = [
	( story, props ) => {
		return (
			<CheckboxControl
				className="woocommerce-enriched-label-story__checkbox-control"
				label={ story( { args: { ...props.args } } ) }
				onChange={ () => {} }
			/>
		);
	},
];
