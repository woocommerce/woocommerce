/**
 * External dependencies
 */
import { TextControl } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

const Example = withState( {
	value: '',
} )( ( { setState, value } ) => {
	return (
		<div>
			<TextControl
				name="text-control"
				label="Enter text here"
				onChange={ ( newValue ) => setState( { value: newValue } ) }
				value={ value }
			/>
			<br />
			<TextControl label="Disabled field" disabled value="" />
		</div>
	);
} );

export const Basic = () => <Example />;

export default {
	title: 'WooCommerce Admin/components/TextControl',
	component: TextControl,
};
