/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ConfettiAnimation } from '@automattic/components';

export const CONFETTI_COLORS: string[] = [
	'#DFD1FB',
	'#CFB9F6',
	'#AD86E9',
	'#966CCF',
	'#966CCF',
	'#674399',
	'#533582',
];

export type ConfettiProps = {
	colors?: string[];
} & React.HTMLAttributes< HTMLSpanElement >;

const Confetti: React.FC< ConfettiProps > = ( {
	colors = CONFETTI_COLORS,
	...props
}: ConfettiProps ) => {
	return <ConfettiAnimation { ...props } colors={ colors } />;
};

export default Confetti;
