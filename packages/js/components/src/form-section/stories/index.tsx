/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';
import { Card, CardBody, TextControl } from '@wordpress/components';

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
			<Card>
				<CardBody>
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
				</CardBody>
			</Card>

			<Card>
				<CardBody>
					<TextControl
						label="My third field"
						onChange={ () => {} }
						value=""
					/>
				</CardBody>
			</Card>
		</FormSection>
	);
};

export default {
	title: 'WooCommerce Admin/components/FormSection',
	component: FormSection,
};
