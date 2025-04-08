/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { Icon, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Country } from './utils';

const Flag = ( { alpha2, src }: { alpha2: string; src: string } ) => (
	<img
		alt={ `${ alpha2 } flag` }
		src={ src }
		className="wcpay-component-phone-number-input__flag"
	/>
);

export const defaultSelectedRender = ( { alpha2, code, flag }: Country ) => (
	<>
		<Flag alpha2={ alpha2 } src={ flag } />
		{ ` +${ code }` }
	</>
);

export const defaultItemRender = ( { alpha2, name, code, flag }: Country ) => (
	<>
		<Flag alpha2={ alpha2 } src={ flag } />
		{ `${ name } +${ code }` }
	</>
);

export const defaultArrowRender = () => (
	<Icon icon={ chevronDown } size={ 18 } />
);
