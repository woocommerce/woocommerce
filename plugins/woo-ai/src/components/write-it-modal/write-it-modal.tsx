/**
 * External dependencies
 */

import {
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
	__experimentalNavigatorButton as NavigatorButton,
	__experimentalNavigatorToParentButton as NavigatorToParentButton,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Modal } from '../modal';

type WriteItModalProps = {
	onRequestClose: () => void;
};

export const WriteItModal = ( { onRequestClose }: WriteItModalProps ) => {
	return (
		<Modal title="Write with AI" onRequestClose={ onRequestClose }>
			<NavigatorProvider initialPath="/">
				<NavigatorScreen path="/">
					<p>This is the home screen.</p>
					<NavigatorButton path="/child">
						Navigate to child screen.
					</NavigatorButton>
				</NavigatorScreen>

				<NavigatorScreen path="/child">
					<p>This is the child screen.</p>
					<NavigatorToParentButton>Go back</NavigatorToParentButton>
				</NavigatorScreen>
			</NavigatorProvider>
		</Modal>
	);
};
