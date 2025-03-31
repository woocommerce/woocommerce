/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
const SidebarSettings = ( { RichTextWithButton } ) => {
	const updateWooMailProperty = ( name: string, value: string ) => {
		console.log(name);
		console.log(value);
	};

	return (
		<>
			<br />
			<RichTextWithButton
				attributeName="subject"
				attributeValue="Subject"
				updateProperty={ updateWooMailProperty }
				label={ __( 'Subject', 'woocommerce' ) }
				placeholder={ __(
					'Eg. The summer sale is here!',
					'woocommerce'
				) }
			/>

			<br />
			<RichTextWithButton
				attributeName="preheader"
				attributeValue="Preheader"
				updateProperty={ updateWooMailProperty }
				label={ __( 'Preheader', 'woocommerce' ) }
				placeholder={ __(
					'Eg. The summer sale is here!',
					'woocommerce'
				) }
			/>
		</>
	);
};

export function modifySidebar() {
	addFilter(
		'woocommerce_email_editor_setting_sidebar_extension_component',
		NAME_SPACE,
		( RichTextWithButton ) => {
			return () => (
				<SidebarSettings RichTextWithButton={ RichTextWithButton } />
			);
		}
	);
}
