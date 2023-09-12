// Reference: https://github.com/WordPress/gutenberg/blob/f9e405e0e53d61cd36af4f3b34f2de75874de1e1/packages/edit-site/src/components/global-styles/screen-colors.js#L23
/**
 * External dependencies
 */
import { privateApis as blockEditorPrivateApis } from '@wordpress/block-editor';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

const {
	useGlobalStyle,
	useGlobalSetting,
	useSettingsForBlockElement,
	ColorPanel: StylesColorPanel,
} = unlock( blockEditorPrivateApis );

export const ColorPanel = () => {
	const [ style ] = useGlobalStyle( '', undefined, 'user', {
		shouldDecodeEncode: false,
	} );
	const [ inheritedStyle, setStyle ] = useGlobalStyle( '', undefined, 'all', {
		shouldDecodeEncode: false,
	} );
	const [ rawSettings ] = useGlobalSetting( '' );
	const settings = useSettingsForBlockElement( rawSettings );

	return (
		<StylesColorPanel
			inheritedValue={ inheritedStyle }
			value={ style }
			onChange={ setStyle }
			settings={ settings }
		/>
	);
};
