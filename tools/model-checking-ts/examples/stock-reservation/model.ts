/* eslint-disable prefer-const -- tla-ts interprets top-level let declarations as model state. */
import {
	STOCK,
	MAX_QUANTITY,
	HOLD_FOR,
	MAX_TIME,
	FOUND_ROWS,
	SPLIT_CHECK_WRITE,
	type Quantity,
} from './parameters';

// One product, fixed stock, default hooks, and unpaid orders.
// Admission is one atomic action; see the SQL locking assumptions in README.md.
type Order = 'A' | 'B';
type Phase = 'idle' | 'query' | 'cleanup' | 'reserved' | 'failed' | 'write';
const PHASES: Phase[] = [
	'idle',
	'query',
	'cleanup',
	'reserved',
	'failed',
	'write',
];

let held: Record< Order, number > = { A: 0, B: 0 };
let expires: Record< Order, number > = { A: 0, B: 0 };
let request: Record< Order, number > = { A: 1, B: 1 };
let attempts: Record< Order, number > = { A: 0, B: 0 };
let phase: Record< Order, Phase > = { A: 'idle', B: 'idle' };
let now = 0;
let seen: Record< Order, number > = { A: 0, B: 0 };

const active = ( order: Order ) =>
	expires[ order ] > now ? held[ order ] : 0;
const otherHeld = ( order: Order ) =>
	order === 'A' ? active( 'B' ) : active( 'A' );

export const start = ( order: Order, quantity: Quantity ) => {
	if (
		phase[ order ] !== 'idle' &&
		phase[ order ] !== 'reserved' &&
		phase[ order ] !== 'failed'
	) {
		return;
	}
	request[ order ] = quantity;
	attempts[ order ] = 0;
	phase[ order ] = 'query';
};

const applyResult = ( order: Order, counted: number ) => {
	const noOp =
		held[ order ] === request[ order ] &&
		expires[ order ] === now + HOLD_FOR;
	attempts[ order ] += 1;
	if ( STOCK - counted >= request[ order ] ) {
		held[ order ] = request[ order ];
		expires[ order ] = now + HOLD_FOR;
		phase[ order ] = noOp && ! FOUND_ROWS ? 'cleanup' : 'reserved';
	} else {
		phase[ order ] = 'cleanup';
	}
};

export const tryReserve = ( order: Order ) => {
	if ( SPLIT_CHECK_WRITE || phase[ order ] !== 'query' ) {
		return;
	}
	applyResult( order, otherHeld( order ) );
};

export const databaseError = ( order: Order ) => {
	if ( phase[ order ] !== 'query' ) {
		return;
	}
	attempts[ order ] += 1;
	phase[ order ] = attempts[ order ] === 3 ? 'cleanup' : 'query';
};

export const cleanup = ( order: Order ) => {
	if ( phase[ order ] !== 'cleanup' ) {
		return;
	}
	held[ order ] = 0;
	expires[ order ] = 0;
	phase[ order ] = 'failed';
};

export const release = ( order: Order ) => {
	if ( held[ order ] <= 0 ) {
		return;
	}
	held[ order ] = 0;
	expires[ order ] = 0;
};

export const tick = () => {
	if ( now >= MAX_TIME ) {
		return;
	}
	now += 1;
};

// Enabled only in the deliberately broken variant, where another call can intervene.
export const readAvailability = ( order: Order ) => {
	if ( ! SPLIT_CHECK_WRITE || phase[ order ] !== 'query' ) {
		return;
	}
	seen[ order ] = otherHeld( order );
	phase[ order ] = 'write';
};

export const writeReservation = ( order: Order ) => {
	if ( ! SPLIT_CHECK_WRITE || phase[ order ] !== 'write' ) {
		return;
	}
	applyResult( order, seen[ order ] );
};

export const TypeOK = () =>
	Object.values( held ).every(
		( value ) => value >= 0 && value <= MAX_QUANTITY
	) &&
	Object.values( expires ).every(
		( value ) => value >= 0 && value <= MAX_TIME + HOLD_FOR
	) &&
	Object.values( request ).every(
		( value ) => value >= 1 && value <= MAX_QUANTITY
	) &&
	Object.values( attempts ).every( ( value ) => value >= 0 && value <= 3 ) &&
	Object.values( phase ).every( ( value ) => PHASES.includes( value ) ) &&
	Object.values( seen ).every(
		( value ) => value >= 0 && value <= 2 * MAX_QUANTITY
	) &&
	now >= 0 &&
	now <= MAX_TIME;

export const NoOverReservation = () => active( 'A' ) + active( 'B' ) <= STOCK;
