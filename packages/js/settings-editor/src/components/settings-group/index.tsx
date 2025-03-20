/**
 * External dependencies
 */
import { __experimentalHeading as Heading } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { DataForm } from '@wordpress/dataviews';
/**
 * Internal dependencies
 */
import { useSettingsForm } from '../../hooks/use-settings-form';
import { sanitizeHTML } from '../../utils';

export const SettingsGroup = ( {
	title,
	desc,
	settings,
}: GroupSettingsField ) => {
	const { data, fields, form, updateField } = useSettingsForm( settings );

	return (
		<fieldset className="woocommerce-settings-group">
			<div className="woocommerce-settings-group-title">
				<Heading level={ 4 }>{ title }</Heading>
				{ desc && (
					<legend
						dangerouslySetInnerHTML={ {
							__html: sanitizeHTML( desc ),
						} }
					/>
				) }
			</div>
			<div className="woocommerce-settings-group-content">
				<DataForm
					data={ data }
					fields={ fields }
					form={ form }
					onChange={ updateField }
				/>
			</div>
		</fieldset>
	);
};
