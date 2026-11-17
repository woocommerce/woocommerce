/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import {
	__experimentalSpacer as Spacer,
	__experimentalView as View,
} from '@wordpress/components';
import { Stack, Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { Navigator } from '../navigator';

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
		<Stack direction="column">
			<View>
				<Spacer marginBottom={ 0 } paddingX={ 4 } paddingY={ 3 }>
					<Stack direction="row" align="center" gap="sm">
						<Navigator.BackButton
							style={ { minWidth: 24, padding: 0 } }
							icon={ chevronLeft }
							size="small"
							aria-label={ __(
								'Navigate to the previous view',
								__i18n_text_domain__
							) }
							onClick={ onBack }
						/>
						<Text variant="heading-md" render={ <h2 /> }>
							{ title }
						</Text>
					</Stack>
				</Spacer>
			</View>
			{ description && (
				<p className="woocommerce-email-editor-styles-header-description">
					{ description }
				</p>
			) }
		</Stack>
	);
}

export default ScreenHeader;
