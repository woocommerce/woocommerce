/**
 * External dependencies
 */
import { withA11y } from '@storybook/addon-a11y';
import { withKnobs } from '@storybook/addon-knobs';
import { addDecorator } from '@storybook/react';

/**
 * Internal dependencies
 */
import './wordpress/css/wp-admin.min.css';
import './style.scss';

addDecorator( withA11y );
addDecorator( withKnobs );
addDecorator( ( Story ) => (
	<div className="woocommerce-layout woocommerce-page">
		<Story />
	</div>
) );
