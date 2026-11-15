/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Let the page drain one macrotask turn.
 *
 * After `page.clock.runFor()` fires a timer, work that timer schedules through a
 * `MessageChannel` (React's scheduler uses one) is still queued. Posting a
 * message of our own and waiting for it puts the next assertion behind that work.
 *
 * @param page The page to flush.
 */
export const flushMacrotask = ( page: Page ): Promise< void > =>
	page.evaluate(
		() =>
			new Promise< void >( ( resolve ) => {
				const channel = new MessageChannel();
				channel.port1.addEventListener(
					'message',
					() => {
						channel.port1.close();
						channel.port2.close();
						resolve();
					},
					{ once: true }
				);
				channel.port1.start();
				channel.port2.postMessage( null );
			} )
	);
