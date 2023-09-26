import * as components from '@wordpress/components';

declare module '@wordpress/components' {
	declare namespace CheckboxControl {
		interface Props {
			indeterminate?: boolean;
		}
	}
}
