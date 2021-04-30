import { CheckoutFlow } from '../specs/shopper/checkout.js';

export let options = {
    scenarios: {
      checkoutFlowSpike: {
        executor: 'ramping-arrival-rate',
        startRate: 1,
        timeUnit: '1s',
        preAllocatedVUs: 1,
        maxVUs: 4,
        stages: [
          { target: 3, duration: '30s' },
          { target: 0, duration: '30s' },
        ],
        exec: 'checkout',
      },
    },
  };
  
export function checkout() {
	CheckoutFlow();
}
