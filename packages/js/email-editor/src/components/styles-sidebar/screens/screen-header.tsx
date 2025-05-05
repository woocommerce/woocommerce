/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import {
	__experimentalHStack as HStack, // eslint-disable-line
	__experimentalVStack as VStack, // eslint-disable-line
	__experimentalSpacer as Spacer, // eslint-disable-line
	__experimentalHeading as Heading, // eslint-disable-line
	__experimentalView as View, // eslint-disable-line
	__experimentalNavigatorToParentButton as NavigatorToParentButton, // eslint-disable-line
} from '@wordpress/components';

type Props = {
	title: string;
	description?: string;
	onBack?: () => void;
};

/**
 * Component for displaying the screen header and optional description based on site editor component:
 * https://github.com/WordPress/gutenberg/blob/7fa03fafeb421ab4c3604564211ce6007cc38e84/packages/edit-site/src/components/global-styles/header.js
 *
 * @param root0
 * @param root0.title
 * @param root0.description
 * @param root0.onBack
 */
export function ScreenHeader( { title, description, onBack }: Props ) {
	return (
		<VStack spacing={ 0 }>
			<View>
				<Spacer marginBottom={ 0 } paddingX={ 4 } paddingY={ 3 }>
					<HStack spacing={ 2 }>
						<NavigatorToParentButton
							style={ { minWidth: 24, padding: 0 } }
							icon={ chevronLeft }
							size="small"
							aria-label={ __(
								'Navigate to the previous view',
								'woocommerce'
							) }
							onClick={ onBack }
						/>
						<Spacer>
							{ /* @ts-expect-error Heading component it's not typed properly in the current components version. */ }
							<Heading
								className="woocommerce-email-editor-styles-header"
								level={ 2 }
								size={ 13 }
							>
								{ title }
							</Heading>
						</Spacer>
					</HStack>
				</Spacer>
			</View>
			{ description && (
				<p className="woocommerce-email-editor-styles-header-description">
					{ description }
				</p>
			) }
		</VStack>
	);
}

export default ScreenHeader;
