/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Text } from '@wordpress/ui';
import {
	WooHeaderNavigationItem,
	WooHeaderPageTitle,
} from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { BackButton } from '~/task-lists/components/back-button';
import { ThemeOptions } from './theme-options';
import { ResetNotice } from './reset-notice';
import type { PurplePrototypeSettings } from './settings';

export const TaskPage = ( {
	settings,
}: {
	settings: PurplePrototypeSettings;
} ) => {
	const title = __( 'Customize your store', 'woocommerce' );

	return (
		<>
			<WooHeaderNavigationItem>
				<BackButton title={ title } />
			</WooHeaderNavigationItem>
			<WooHeaderPageTitle>{ title }</WooHeaderPageTitle>
			<div className="purple-cys-task-page">
				<ResetNotice />
				<Text variant="heading-xl" render={ <h1 /> }>
					{ __( 'Choose how your store looks', 'woocommerce' ) }
				</Text>
				<ThemeOptions settings={ settings } />
			</div>
		</>
	);
};
