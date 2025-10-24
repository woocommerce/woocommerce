# Claude Code Documentation for WooCommerce Settings Payments

**Scope**: Payment gateway UI architecture and patterns
**Location**: `plugins/woocommerce/client/admin/client/settings-payments`

**See also:**
- `../CLAUDE.md` - Testing, linting, and build commands
- `packages/js/data/src/payment-settings/` - Data layer and types

## Quick Workflow

**Modifying payment gateway features (data + UI must be updated together):**
1. Update types: `packages/js/data/src/payment-settings/types.ts`
2. Update test stubs: `packages/js/data/src/payment-settings/test/helpers/stub.ts`
3. Update UI: `client/settings-payments/components/`
4. Test: `cd ../.. && pnpm run test:js -- settings-payments && pnpm run ts:check`

## Architecture

### Data/UI Separation (Critical Pattern)

**Data layer** (must be updated first):
- Types: `packages/js/data/src/payment-settings/types.ts`
- Test stubs: `packages/js/data/src/payment-settings/test/helpers/stub.ts`
- Key types: `PaymentGatewayProvider`, `PaymentsProviderOnboardingState`, `OfflinePaymentMethodProvider`

**UI layer** (depends on data layer):
- Components: `client/settings-payments/components/`
- Tests: Component-level tests in `test/` subdirectories

### Directory Structure

```
settings-payments/
├── components/               # UI components
│   ├── buttons/             # Action buttons (CompleteSetup, Enable, etc.)
│   ├── status-badge/        # Status badge with popover support
│   ├── payment-gateway-list-item/  # Main list item orchestrator
│   └── ...
├── onboarding/              # Onboarding flows
│   └── providers/           # Provider-specific onboarding
└── utils/                   # Utility functions
```

## Key Patterns

### Security: Disabled/Unsupported Features

When features are disabled or unsupported, use **minimal props** to avoid exposing sensitive actions:
- Empty strings for URLs (`onboardingHref=""`)
- No-op functions for callbacks (`setOnboardingModalOpen={() => {}}`)
- Omit sensitive props (`onboardingType`, `incentive`, `acceptIncentive`)
- Explicitly set `disabled={true}`

**Rationale**: Prevents inadvertent triggering of onboarding actions even if button is somehow activated.

### Component Architecture Pattern

**Status determination priority** (in `PaymentGatewayListItem.determineGatewayStatus()`):
1. not_supported → needs_setup → test states → active/inactive

**Button pattern**:
- Accept `gatewayProvider` prop for gateway data
- Support `disabled` prop (combines with internal states)
- Record analytics events on interactions

**Status badge pattern**:
- Supports `popoverContent` for additional context messages
- Located in `components/status-badge/`

### REST API Integration

**Endpoint**: `/wc-admin/settings/payments/providers`

**Key fields**:
- `onboarding.state.supported` - Whether onboarding is supported
- `onboarding.messages.not_supported` - Why not supported (shown in popover)
- `state.account_connected` - Account connection status
- `state.enabled` - Gateway enabled status

## Notes

- Tests located in `test/` subdirectories alongside components
- Status badge types defined in `StatusBadgeProps` interface
- When adding new statuses: update type, CSS class, message, tests
- Status checks evaluated in priority order (check code for current priority)