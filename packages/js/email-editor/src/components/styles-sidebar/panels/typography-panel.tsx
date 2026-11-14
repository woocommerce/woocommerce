/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	__experimentalNavigatorButton as NavigatorButton,
	FlexItem,
} from '@wordpress/components';
import { Stack, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { useEmailStyles } from '../../../hooks';
import { getElementStyles } from '../utils';
import { recordEvent } from '../../../events';

function ElementItem( { element, label }: { element: string; label: string } ) {
	const { styles } = useEmailStyles();
	const elementStyles = getElementStyles( styles, element, null, true );

	const {
		fontFamily,
		fontStyle,
		fontWeight,
		letterSpacing,
		textDecoration,
		textTransform,
	} = elementStyles.typography;

	const textColor = elementStyles.color?.text || 'inherit';
	const background = elementStyles.color?.background || '#f0f0f0';
	const navigationButtonLabel = sprintf(
		// translators: %s: is a subset of Typography, e.g., 'text' or 'links'.
		__( 'Typography %s styles', __i18n_text_domain__ ),
		label
	);

	return (
		<Item>
			<NavigatorButton
				path={ `/typography/${ element }` }
				aria-label={ navigationButtonLabel }
				onClick={ () =>
					recordEvent(
						'styles_sidebar_screen_typography_button_click',
						{
							element,
							label,
							path: `typography/${ element }`,
						}
					)
				}
			>
				<Stack direction="row" align="center" gap="sm">
					<FlexItem
						className="edit-site-global-styles-screen-typography__indicator"
						style={ {
							fontFamily: fontFamily ?? 'serif',
							background,
							color: textColor,
							fontStyle: fontStyle ?? 'normal',
							fontWeight: fontWeight ?? 'normal',
							letterSpacing: letterSpacing ?? 'normal',
							textDecoration:
								textDecoration ??
								( element === 'link' ? 'underline' : 'none' ),
							textTransform: textTransform ?? 'none',
						} }
					>
						Aa
					</FlexItem>
					<FlexItem>{ label }</FlexItem>
				</Stack>
			</NavigatorButton>
		</Item>
	);
}

export function TypographyPanel() {
	return (
		<Stack
			direction="column"
			gap="md"
			className="woocommerce-email-editor-styles-screen-body"
		>
			<Text variant="heading-sm" render={ <h3 /> }>
				{ __( 'Elements', __i18n_text_domain__ ) }
			</Text>
			<ItemGroup isBordered isSeparated size="small">
				<ElementItem
					element="text"
					label={ __( 'Text', __i18n_text_domain__ ) }
				/>
				<ElementItem
					element="link"
					label={ __( 'Links', __i18n_text_domain__ ) }
				/>
				<ElementItem
					element="heading"
					label={ __( 'Headings', __i18n_text_domain__ ) }
				/>
				<ElementItem
					element="button"
					label={ __( 'Buttons', __i18n_text_domain__ ) }
				/>
			</ItemGroup>
		</Stack>
	);
}

export default TypographyPanel;
