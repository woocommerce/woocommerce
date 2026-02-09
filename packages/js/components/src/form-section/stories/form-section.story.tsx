/**
 * External dependencies
 */
import { Button, Card, CardBody, TextControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FormSection } from '../';
import Pill from '../../pill';

export const Basic = () => {
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

export const CustomElements = () => {
	return (
		<FormSection
			title={
				<>
					Custom elements <Pill>Cool</Pill>
				</>
			}
			description={
				<>
					<p>Some text to describe what this section covers</p>
					<Button variant="link">Read more</Button>
				</>
			}
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
		</FormSection>
	);
};

export default {
	title: 'Components/FormSection',
	component: FormSection,
};
