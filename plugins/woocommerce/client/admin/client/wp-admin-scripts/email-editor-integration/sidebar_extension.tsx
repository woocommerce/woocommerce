/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	useEntityRecord,
	store as coreDataStore,
	Post,
} from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { EntityRecordResolution } from '@wordpress/core-data/build-types/hooks/use-entity-record';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

const SidebarExtensionComponent = () => {
	const { current_post_id, current_post_type, email_types } =
		window.WooCommerceEmailEditor;
	const email = useEntityRecord(
		'postType',
		current_post_type,
		current_post_id
	) as EntityRecordResolution< Post >;
	const { editEntityRecord } = useDispatch( coreDataStore );
	const email_type_options = [ { value: '', label: '---' }, ...email_types ];

	return (
		<SelectControl
			label={ __( 'Email type', 'woocommerce' ) }
			options={ email_type_options }
			value={ email.editedRecord.slug }
			onChange={ ( value ) => {
				editEntityRecord(
					'postType',
					current_post_type,
					current_post_id,
					{ slug: value }
				);
			} }
			__nextHasNoMarginBottom
		/>
	);
};

export function modifySidebar() {
	addFilter(
		'woocommerce_email_editor_setting_sidebar_extension_component',
		NAME_SPACE,
		() => SidebarExtensionComponent
	);
}
