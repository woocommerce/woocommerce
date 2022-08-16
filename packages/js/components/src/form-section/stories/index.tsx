/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { FormSection } from '../';

export const Basic: React.FC = () => {
	return (
		<FormSection
			title="My form section"
			description="Some text to describe what this section covers"
		>
			<TextControl
				label="My first field"
				onChange={ () => {} }
				value=""
			/>
			<TextControl
				label="My second field"
				onChange={ () => {} }
				value=""
			/>
		</FormSection>
	);
};

export default {
	title: 'WooCommerce Admin/components/FormSection',
	component: FormSection,
};
