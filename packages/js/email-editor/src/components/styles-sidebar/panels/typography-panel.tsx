/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	__experimentalItemGroup as ItemGroup, // eslint-disable-line
	__experimentalItem as Item, // eslint-disable-line
	__experimentalVStack as VStack, // eslint-disable-line
	__experimentalHStack as HStack, // eslint-disable-line
	__experimentalHeading as Heading, // eslint-disable-line
	__experimentalNavigatorButton as NavigatorButton, // eslint-disable-line
	FlexItem,
	Card,
	CardBody,
} from '@wordpress/components';

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
		__( 'Typography %s styles', 'woocommerce' ),
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
				<HStack justify="flex-start">
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
				</HStack>
			</NavigatorButton>
		</Item>
	);
}

export function TypographyPanel() {
	return (
		<Card size="small" variant="primary" isBorderless>
			<CardBody>
				<VStack spacing={ 3 }>
					<Heading
						level={ 3 }
						className="edit-site-global-styles-subtitle"
					>
						{ __( 'Elements', 'woocommerce' ) }
					</Heading>
					<ItemGroup isBordered isSeparated size="small">
						<ElementItem
							element="text"
							label={ __( 'Text', 'woocommerce' ) }
						/>
						<ElementItem
							element="link"
							label={ __( 'Links', 'woocommerce' ) }
						/>
						<ElementItem
							element="heading"
							label={ __( 'Headings', 'woocommerce' ) }
						/>
						<ElementItem
							element="button"
							label={ __( 'Buttons', 'woocommerce' ) }
						/>
					</ItemGroup>
				</VStack>
			</CardBody>
		</Card>
	);
}

export default TypographyPanel;
