/**
 * External dependencies
 */
import { text } from '@storybook/addon-knobs';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import CheckboxControl from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/CheckboxControl',
	component: CheckboxControl,
};

export const Default = () => {
	const [ checked, setChecked ] = useState( false );

	return (
		<CheckboxControl
			label={ text( 'Label', 'Yes please' ) }
			checked={ checked }
			onChange={ ( value ) => setChecked( value ) }
		/>
	);
};
