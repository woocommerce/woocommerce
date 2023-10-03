/**
 * Internal dependencies
 */
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import { InstallMobileAppInfo } from '../components/InstallMobileAppInfo';

export const InstallMobileAppPage = () => {
	return (
		<ModalContentLayoutWithTitle>
			<InstallMobileAppInfo />
		</ModalContentLayoutWithTitle>
	);
};
