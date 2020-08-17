import { CustomerFlow, StoreOwnerFlow } from './src/flows';

import {
	completeOnboardingWizard,
	completeOldSetupWizard,
	createSimpleProduct,
	createVariableProduct,
	verifyAndPublish,
} from './src/components';

import {
	clearAndFillInput,
	clickTab,
	settingsPageSaveChanges,
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	unsetCheckbox,
	uiUnblocked,
	verifyPublishAndTrash,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
	verifyValueOfInputField,
} from './src/page-utils';

module.exports = {
	CustomerFlow,
	StoreOwnerFlow,
	completeOnboardingWizard,
	completeOldSetupWizard,
	createSimpleProduct,
	createVariableProduct,
	verifyAndPublish,
	clearAndFillInput,
	clickTab,
	settingsPageSaveChanges,
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	unsetCheckbox,
	uiUnblocked,
	verifyPublishAndTrash,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset,
	verifyValueOfInputField
}
