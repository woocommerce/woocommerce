import { Actions, IncomeMessage, OutcomeMessage } from './types';

export function sendOutcomeMessage< T extends keyof Actions >(
	message: OutcomeMessage< T >,
	options?: WindowPostMessageOptions
) {
	postMessage( message, options );
}

export function sendIncomeMessage< T extends keyof Actions >(
	worker: Worker,
	message: IncomeMessage< T >,
	options?: StructuredSerializeOptions
) {
	worker.postMessage( message, options );
}
