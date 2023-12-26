/**
 * Internal dependencies
 */
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import { MobileAppInstallationInfo } from '../components/MobileAppInstallationInfo';

export const MobileAppInstallPage = () => {
	return (
		<ModalContentLayoutWithTitle>
			<MobileAppInstallationInfo />
		</ModalContentLayoutWithTitle>
	);
};
