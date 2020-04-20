  import { CustomerFlow, StoreOwnerFlow } from './flows';

  import {
	completeOldSetupWizard,
	createSimpleProduct,
	createVariableProduct,
	verifyAndPublish,
  } from './components';

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
  } from './page-utils';

  module.exports = {
  CustomerFlow,
  StoreOwnerFlow,
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