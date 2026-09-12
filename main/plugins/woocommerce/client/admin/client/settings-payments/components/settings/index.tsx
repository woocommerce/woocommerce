/**
 * External dependencies
 */
import { Card, CardBody } from '@wordpress/components';
import { useEffect } from 'react';

/**
 * Internal dependencies
 */
import './settings.scss';

type SettingsProps = {
	children: React.ReactNode;
};

/**
 * Wraps the full form layout area.
 */
const Layout = ( { children }: SettingsProps ) => {
	// Add a class to the body element when the settings element is mounted.
	useEffect( () => {
		const el = document.getElementById( 'wpbody' );
		if ( el && el.querySelector( '.settings-layout' ) ) {
			el.classList.add( 'has-settings-layout' );
		}
	}, [] );

	return <div className="settings-layout">{ children }</div>;
};

/**
 * Defines a labeled section with a heading, description, and content.
 */
const Section = ( {
	title,
	description,
	children,
	id,
}: {
	title: string;
	description: string;
	children?: React.ReactNode;
	id?: string;
} ) => (
	<div className="settings-section" id={ id }>
		<div className="settings-section__details">
			<h2>{ title }</h2>
			<p>{ description }</p>
		</div>
		<div className="settings-section__controls">{ children }</div>
	</div>
);

/**
 * Displays action buttons (e.g. Save).
 */
const Actions = ( { children }: SettingsProps ) => (
	<Card className={ 'settings-card__wrapper ' }>
		<CardBody className={ 'form__actions' }>{ children }</CardBody>
	</Card>
);

/**
 * Wraps form fields and handles form submission.
 */
const Form = ( {
	children,
	onSubmit,
}: {
	children: React.ReactNode;
	onSubmit?: React.FormEventHandler< HTMLFormElement >;
} ) => (
	<form onSubmit={ onSubmit } className="settings-form">
		{ children }
	</form>
);

/**
 * The `Settings` component provides a composable structure for building
 * administrative settings forms.
 *
 * It includes the following subcomponents:
 *
 * - `Settings.Layout`: Wraps the full form layout area.
 * - `Settings.Form`: Wraps form fields and handles form submission.
 * - `Settings.Section`: Defines a labeled section with a heading, description, and content.
 * - `Settings.Actions`: Displays action buttons (e.g. Save).
 *
 * Example usage:
 * ```tsx
 * <Settings>
 *   <Settings.Layout>
 *     <Settings.Form onSubmit={handleSubmit}>
 *       <Settings.Section title="First Section" description="Description">
 *         <input />
 *       </Settings.Section>
 *       <Settings.Section title="Second Section" description="Description">
 *           <p>Section content goes here.</p>
 *       </Section>
 *       <Settings.Actions>
 *         <button type="submit">Save</button>
 *       </Settings.Actions>
 *     </Settings.Form>
 *   </Settings.Layout>
 * </Settings>
 * ```
 */
export const Settings = ( { children }: SettingsProps ) => <>{ children }</>;

Settings.Layout = Layout;
Settings.Section = Section;
Settings.Actions = Actions;
Settings.Form = Form;
