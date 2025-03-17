/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import {
	Button,
	Dropdown,
	VisuallyHidden,
	__experimentalText as Text,
	TextControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { storeName, editorCurrentPostType } from '../../store';
import { recordEvent, recordEventOnce } from '../../events';

// @see https://github.com/WordPress/gutenberg/blob/5e0ffdbc36cb2e967dfa6a6b812a10a2e56a598f/packages/edit-post/src/components/header/document-actions/index.js

export function CampaignName() {
	const { showIconLabels } = useSelect(
		( select ) => ( {
			showIconLabels:
				select( storeName ).isFeatureActive( 'showIconLabels' ),
			postId: select( storeName ).getEmailPostId(),
		} ),
		[]
	);

	const [ emailTitle = '', setTitle ] = useEntityProp(
		'postType',
		editorCurrentPostType,
		'title'
	);

	const titleRef = useRef( null );
	return (
		<div
			ref={ titleRef }
			className="woocommerce-email-editor-campaign-name"
		>
			<Dropdown
				popoverProps={ {
					placement: 'bottom',
					anchor: titleRef.current,
				} }
				contentClassName="woocommerce-email-editor-campaign-name-dropdown"
				renderToggle={ ( { isOpen, onToggle } ) => (
					<>
						<Button
							onClick={ () => {
								onToggle();
								recordEvent(
									'header_campaign_name_email_title_clicked',
									{ isOpen }
								);
							} }
							className="woocommerce-email-campaign-name-link"
						>
							<Text size="body" as="h1">
								<VisuallyHidden as="span">
									{ __( 'Editing email:', 'woocommerce' ) }
								</VisuallyHidden>
								{ emailTitle }
							</Text>
						</Button>
						<Button
							className="woocommerce-email-campaign-name__toggle"
							icon={ chevronDown }
							aria-expanded={ isOpen }
							aria-haspopup="true"
							onClick={ () => {
								onToggle();
								recordEvent(
									'header_campaign_name_toggle_icon_clicked',
									{ isOpen }
								);
							} }
							label={ __(
								'Change campaign name',
								'woocommerce'
							) }
						>
							{ showIconLabels && __( 'Rename', 'woocommerce' ) }
						</Button>
					</>
				) }
				renderContent={ () => (
					<div className="woocommerce-email-editor-email-title-edit">
						<TextControl
							label={ __( 'Campaign name', 'woocommerce' ) }
							value={ emailTitle }
							onChange={ ( newTitle ) => {
								setTitle( newTitle );
								recordEventOnce(
									'header_campaign_name_title_updated'
								);
							} }
							name="campaign_name"
							help={ __(
								`Name your email campaign to indicate its purpose. This would only be visible to you and not shown to your subscribers.`,
								'woocommerce'
							) }
						/>
					</div>
				) }
			/>
		</div>
	);
}
