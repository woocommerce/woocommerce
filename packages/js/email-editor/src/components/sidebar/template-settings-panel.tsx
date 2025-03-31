/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';

interface TemplatePanelSection {
	id: string;
	render: () => JSX.Element | null;
}

export function TemplateSettingsPanel() {
	// Allow plugins to add custom template sections
	const templateSections = applyFilters(
		'woocommerce_email_editor_template_sections',
		[]
	) as TemplatePanelSection[];

	if ( templateSections.length === 0 ) {
		return null;
	}

	return (
		<>
			{ templateSections.map( ( section ) => (
				<div key={ section.id }>{ section.render() }</div>
			) ) }
		</>
	);
}
