/**
 * External dependencies
 */
import { Tabs } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { TemplateCategory } from '../../store';

type Props = {
	templateCategories: Array< { name: TemplateCategory; label: string } >;
};

export function TemplateCategoriesListSidebar( { templateCategories }: Props ) {
	return (
		<div className="email-editor-template-select__sidebar">
			<Tabs.List>
				{ templateCategories.map( ( { name, label } ) => (
					<Tabs.Tab key={ name } value={ name }>
						{ label }
					</Tabs.Tab>
				) ) }
			</Tabs.List>
		</div>
	);
}
