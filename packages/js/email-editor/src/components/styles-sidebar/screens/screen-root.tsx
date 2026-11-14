/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { typography, color, background, layout } from '@wordpress/icons';
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	__experimentalNavigatorButton as NavigatorButton,
	Icon,
	FlexItem,
} from '@wordpress/components';
import { Card, Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { Preview } from './preview';
import { useHasBackgroundScreen } from '../hooks';
import { recordEvent } from '../../../events';

export function ScreenRoot(): JSX.Element {
	const hasBackgroundScreen = useHasBackgroundScreen();
	return (
		<Stack
			direction="column"
			gap="lg"
			className="woocommerce-email-editor-styles-screen-body"
		>
			<Card.Root>
				<Card.Content>
					<Card.FullBleed>
						<Preview />
					</Card.FullBleed>
				</Card.Content>
			</Card.Root>
			<ItemGroup>
				<NavigatorButton
					path="/typography"
					onClick={ () =>
						recordEvent( 'styles_sidebar_navigation_click', {
							path: 'typography',
						} )
					}
				>
					<Item>
						<Stack direction="row" align="center" gap="sm">
							<Icon icon={ typography } size={ 24 } />
							<FlexItem>
								{ __( 'Typography', __i18n_text_domain__ ) }
							</FlexItem>
						</Stack>
					</Item>
				</NavigatorButton>
				<NavigatorButton
					path="/colors"
					onClick={ () =>
						recordEvent( 'styles_sidebar_navigation_click', {
							path: 'colors',
						} )
					}
				>
					<Item>
						<Stack direction="row" align="center" gap="sm">
							<Icon icon={ color } size={ 24 } />
							<FlexItem>
								{ __( 'Colors', __i18n_text_domain__ ) }
							</FlexItem>
						</Stack>
					</Item>
				</NavigatorButton>
				{ hasBackgroundScreen && (
					<NavigatorButton
						path="/background"
						onClick={ () =>
							recordEvent( 'styles_sidebar_navigation_click', {
								path: 'background',
							} )
						}
					>
						<Item>
							<Stack direction="row" align="center" gap="sm">
								<Icon icon={ background } size={ 24 } />
								<FlexItem>
									{ __( 'Background', __i18n_text_domain__ ) }
								</FlexItem>
							</Stack>
						</Item>
					</NavigatorButton>
				) }
				<NavigatorButton
					path="/layout"
					onClick={ () =>
						recordEvent( 'styles_sidebar_navigation_click', {
							path: 'layout',
						} )
					}
				>
					<Item>
						<Stack direction="row" align="center" gap="sm">
							<Icon icon={ layout } size={ 24 } />
							<FlexItem>
								{ __( 'Layout', __i18n_text_domain__ ) }
							</FlexItem>
						</Stack>
					</Item>
				</NavigatorButton>
			</ItemGroup>
		</Stack>
	);
}
