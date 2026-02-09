/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { typography, color, layout } from '@wordpress/icons';
import {
	__experimentalVStack as VStack, // eslint-disable-line
	Card,
	CardBody,
	CardMedia,
	__experimentalItemGroup as ItemGroup, // eslint-disable-line
	__experimentalItem as Item, // eslint-disable-line
	__experimentalHStack as HStack, // eslint-disable-line
	__experimentalNavigatorButton as NavigatorButton, // eslint-disable-line
	Icon,
	FlexItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Preview } from './preview';
import { recordEvent } from '../../../events';

export function ScreenRoot(): JSX.Element {
	return (
		<Card
			size="small"
			className="edit-site-global-styles-screen-root"
			variant="primary"
		>
			<CardBody>
				<VStack spacing={ 4 }>
					<Card>
						<CardMedia>
							<Preview />
						</CardMedia>
					</Card>
					<ItemGroup>
						<NavigatorButton
							path="/typography"
							onClick={ () =>
								recordEvent(
									'styles_sidebar_navigation_click',
									{ path: 'typography' }
								)
							}
						>
							<Item>
								<HStack justify="flex-start">
									<Icon icon={ typography } size={ 24 } />
									<FlexItem>
										{ __( 'Typography', 'woocommerce' ) }
									</FlexItem>
								</HStack>
							</Item>
						</NavigatorButton>
						<NavigatorButton
							path="/colors"
							onClick={ () =>
								recordEvent(
									'styles_sidebar_navigation_click',
									{ path: 'colors' }
								)
							}
						>
							<Item>
								<HStack justify="flex-start">
									<Icon icon={ color } size={ 24 } />
									<FlexItem>
										{ __( 'Colors', 'woocommerce' ) }
									</FlexItem>
								</HStack>
							</Item>
						</NavigatorButton>
						<NavigatorButton
							path="/layout"
							onClick={ () =>
								recordEvent(
									'styles_sidebar_navigation_click',
									{ path: 'layout' }
								)
							}
						>
							<Item>
								<HStack justify="flex-start">
									<Icon icon={ layout } size={ 24 } />
									<FlexItem>
										{ __( 'Layout', 'woocommerce' ) }
									</FlexItem>
								</HStack>
							</Item>
						</NavigatorButton>
					</ItemGroup>
				</VStack>
			</CardBody>
		</Card>
	);
}
