# Linear OAuth Refresh Token Migration - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrate the "GitHub WooCommerce workflow" Linear OAuth integration from long-lived tokens to short-lived access tokens with refresh token rotation, before the April 1, 2026 deprecation deadline.

**Architecture:** Create a reusable composite GitHub Action that handles Linear token refresh at workflow start. Both release workflows call this action first, then use the short-lived access token for all Linear SDK calls. After each refresh, the action updates the stored refresh token in GitHub secrets via the GitHub API so the next run has a valid refresh token.

**Tech Stack:** GitHub Actions (composite action), Linear OAuth2 API, GitHub REST API (for secret rotation), Node.js (inline scripts), `@linear/sdk@71.0.0`, `libsodium-wrappers` (for encrypting GitHub secrets).

---

## Flow Diagram

```
                    CURRENT FLOW (long-lived token)
                    ================================

  GitHub Secret                    Linear API
  +-----------------+             +------------------+
  | LINEAR_OAUTH_   |   apiKey    |                  |
  | TOKEN           |------------>|  issues()        |
  | (10-year token) |             |  createIssue()   |
  +-----------------+             |  users()         |
                                  +------------------+


                    NEW FLOW (refresh token rotation)
                    =================================

  +-----------+     1. Workflow starts
  | Workflow  |------------------------------+
  | Trigger   |                              |
  +-----------+                              v
                                 +--------------------------+
                                 | linear-auth action       |
                                 |                          |
  GitHub Secrets                 |  2. Read refresh token   |
  +---------------------+       |     from secrets         |
  | LINEAR_CLIENT_ID    |<------|                          |
  | LINEAR_CLIENT_SECRET|       |  3. POST /oauth/token    |
  | LINEAR_REFRESH_TOKEN|       |     grant_type=          |
  | GH_TOKEN_FOR_SECRETS|       |     refresh_token        |
  +---------------------+       |                          |
         ^                      |  4. Receive new          |
         |                      |     access_token +       |
         |                      |     refresh_token        |
         |                      |                          |
         +----------------------|  5. Update GitHub secret |
          6. Store new          |     LINEAR_REFRESH_TOKEN |
             refresh_token      |     via GitHub API       |
                                |                          |
                                |  7. Output: access_token |
                                +-----------+--------------+
                                            |
                                            v
                                 +---------------------+
                                 | Workflow steps       |
                                 |                      |
                                 | linearClient =       |
                                 |   new LinearClient({ |
                                 |     accessToken:     |
                                 |       <short-lived>  |  <-- 24h expiry
                                 |   })                 |
                                 |                      |
                                 | issues()             |
                                 | createIssue()        |
                                 | users()              |
                                 +---------------------+


                    ONE-TIME MIGRATION (run once)
                    =============================

  +---------------------+     POST /oauth/migrate_old_token
  | Current long-lived  |-----------------------------------+
  | LINEAR_OAUTH_TOKEN  |     + client_id                   |
  +---------------------+     + client_secret               v
                                              +-------------------+
                                              | Linear API        |
                                              |                   |
                                              | Returns:          |
                                              |  access_token     |
                                              |  refresh_token    |
                                              |  expires_in       |
                                              +-------------------+
                                                       |
                                                       v
                                              Store refresh_token
                                              as LINEAR_REFRESH_TOKEN
                                              GitHub secret
```

## Secrets Required

| Secret | Description | When to set |
|--------|-------------|-------------|
| `LINEAR_CLIENT_ID` | OAuth app client ID from Linear settings | Before migration |
| `LINEAR_CLIENT_SECRET` | OAuth app client secret from Linear settings | Before migration |
| `LINEAR_REFRESH_TOKEN` | Refresh token (rotated automatically) | Set after initial migration |
| `LINEAR_TEAM_ID` | Already exists, no change needed | Already set |
| `LINEAR_OAUTH_TOKEN` | Remove after migration is verified | Remove after verification |

A `GITHUB_TOKEN` with `secrets` write permission is needed to update the refresh token secret. The built-in `secrets.GITHUB_TOKEN` does NOT have permission to write secrets, so a GitHub App token or PAT with `repo` scope stored as e.g. `GH_SECRETS_TOKEN` is required.

---

## Task 0: Prerequisites (Manual - Admin Required)

These steps require admin access to the Linear OAuth app and the GitHub repo settings.

**Step 1:** Open the Linear OAuth app settings for "GitHub WooCommerce workflow"
- Enable refresh tokens in the app settings
- Note the `client_id` and `client_secret`

**Step 2:** Add new GitHub secrets to `woocommerce/woocommerce`:
- `LINEAR_CLIENT_ID` = the OAuth app client ID
- `LINEAR_CLIENT_SECRET` = the OAuth app client secret
- `GH_SECRETS_TOKEN` = a GitHub PAT (or App token) with `repo` scope for updating secrets

**Step 3:** Run the one-time token migration (can be done via `curl`):
```bash
curl -X POST https://api.linear.app/oauth/migrate_old_token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "token=<CURRENT_LINEAR_OAUTH_TOKEN>&client_id=<CLIENT_ID>&client_secret=<CLIENT_SECRET>"
```
Response: `{ "access_token": "...", "refresh_token": "...", "token_type": "Bearer", "expires_in": 86400 }`

**Step 4:** Store the `refresh_token` from the response as the `LINEAR_REFRESH_TOKEN` GitHub secret.

---

## Task 1: Create the `linear-auth` Composite Action

**Files:**
- Create: `.github/actions/linear-auth/action.yml`

**Step 1: Create the composite action file**

This action:
1. Takes client credentials + refresh token as inputs
2. Calls Linear's token endpoint to get a new access token
3. Updates the `LINEAR_REFRESH_TOKEN` GitHub secret via GitHub API
4. Outputs the short-lived access token

```yaml
name: 'Linear OAuth Token Refresh'
description: 'Refreshes a Linear OAuth access token using a refresh token and rotates the stored refresh token in GitHub secrets.'

inputs:
  linear-client-id:
    description: 'Linear OAuth app client ID'
    required: true
  linear-client-secret:
    description: 'Linear OAuth app client secret'
    required: true
  linear-refresh-token:
    description: 'Current Linear refresh token'
    required: true
  gh-secrets-token:
    description: 'GitHub token with permission to update repository secrets'
    required: true

outputs:
  access-token:
    description: 'Short-lived Linear access token (24h)'
    value: ${{ steps.refresh.outputs.access-token }}

runs:
  using: 'composite'
  steps:
    - name: Install dependencies
      shell: bash
      run: npm install libsodium-wrappers@0.7.15

    - name: Refresh Linear OAuth token
      id: refresh
      uses: actions/github-script@v7
      env:
        LINEAR_CLIENT_ID: ${{ inputs.linear-client-id }}
        LINEAR_CLIENT_SECRET: ${{ inputs.linear-client-secret }}
        LINEAR_REFRESH_TOKEN: ${{ inputs.linear-refresh-token }}
        GH_SECRETS_TOKEN: ${{ inputs.gh-secrets-token }}
      with:
        script: |
          // 1. Exchange refresh token for new access token + refresh token.
          const params = new URLSearchParams( {
            grant_type: 'refresh_token',
            client_id: process.env.LINEAR_CLIENT_ID,
            client_secret: process.env.LINEAR_CLIENT_SECRET,
            refresh_token: process.env.LINEAR_REFRESH_TOKEN,
          } );

          const response = await fetch( 'https://api.linear.app/oauth/token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString(),
          } );

          if ( ! response.ok ) {
            const errorBody = await response.text();
            core.setFailed( `Linear token refresh failed (${ response.status }): ${ errorBody }` );
            return;
          }

          const tokenData = await response.json();
          const newAccessToken = tokenData.access_token;
          const newRefreshToken = tokenData.refresh_token;

          if ( ! newAccessToken || ! newRefreshToken ) {
            core.setFailed( 'Linear token refresh response missing access_token or refresh_token' );
            return;
          }

          core.setSecret( newAccessToken );
          core.setSecret( newRefreshToken );
          core.setOutput( 'access-token', newAccessToken );

          console.log( `Access token expires in ${ tokenData.expires_in } seconds` );

          // 2. Update the LINEAR_REFRESH_TOKEN secret in GitHub.
          //    GitHub secrets must be encrypted with the repo's public key using libsodium.
          const sodium = require( 'libsodium-wrappers' );
          await sodium.ready;

          const octokit = github.getOctokit( process.env.GH_SECRETS_TOKEN );
          const { owner, repo } = context.repo;

          // Get the repo's public key for secret encryption.
          const { data: publicKeyData } = await octokit.rest.actions.getRepoPublicKey( {
            owner,
            repo,
          } );

          // Encrypt the new refresh token.
          const key = sodium.from_base64( publicKeyData.key, sodium.base64_variants.ORIGINAL );
          const encryptedBytes = sodium.crypto_box_seal( sodium.from_string( newRefreshToken ), key );
          const encryptedValue = sodium.to_base64( encryptedBytes, sodium.base64_variants.ORIGINAL );

          // Update the secret.
          await octokit.rest.actions.createOrUpdateRepoSecret( {
            owner,
            repo,
            secret_name: 'LINEAR_REFRESH_TOKEN',
            encrypted_value: encryptedValue,
            key_id: publicKeyData.key_id,
          } );

          console.log( 'Successfully rotated LINEAR_REFRESH_TOKEN secret' );
```

**Step 2: Verify the file is valid YAML**

Run: `npx yaml-lint .github/actions/linear-auth/action.yml` or review manually.

**Step 3: Commit**

```bash
git add .github/actions/linear-auth/action.yml
git commit -m "ci: add linear-auth composite action for OAuth token refresh"
```

---

## Task 2: Update `release-assignment.yml` to Use the Composite Action

**Files:**
- Modify: `.github/workflows/release-assignment.yml`

**Step 1: Add the `linear-auth` step to the `create-parent-tracking-issue` job**

In the `create-parent-tracking-issue` job, add the auth step right after the `Install linear-sdk` step and before the first Linear API call:

```yaml
      - name: Refresh Linear OAuth token
        id: linear-auth
        uses: ./.github/actions/linear-auth
        with:
          linear-client-id: ${{ secrets.LINEAR_CLIENT_ID }}
          linear-client-secret: ${{ secrets.LINEAR_CLIENT_SECRET }}
          linear-refresh-token: ${{ secrets.LINEAR_REFRESH_TOKEN }}
          gh-secrets-token: ${{ secrets.GH_SECRETS_TOKEN }}
```

**Step 2: Replace all `apiKey` usages with `accessToken` in the `create-parent-tracking-issue` job**

In every `LinearClient` instantiation within this job, change:
```javascript
const linearClient = new LinearClient( {
  apiKey: '${{ secrets.LINEAR_OAUTH_TOKEN }}'
} );
```
to:
```javascript
const linearClient = new LinearClient( {
  accessToken: '${{ steps.linear-auth.outputs.access-token }}'
} );
```

There are 4 instantiations in this job (steps: check-existing-issue, get-linear-data, get-linear-user, create-tracking-issue).

**Step 3: Update the `create-sub-tracking-issues` job secrets block**

Change the secrets passed to the reusable workflow:
```yaml
    secrets:
      LINEAR_ACCESS_TOKEN: ${{ steps.linear-auth.outputs.access-token }}  # <-- from parent job auth
      LINEAR_TEAM_ID: ${{ secrets.LINEAR_TEAM_ID }}
```

Wait -- the sub-issues job calls the reusable workflow and needs a token too. Since the sub-issues job runs as a separate matrix job, and the access token from the parent is valid for 24h, we can pass it through. But we need the parent job to expose the token as an output.

Add to the `create-parent-tracking-issue` job outputs:
```yaml
    outputs:
      sub-issues-matrix: ${{ steps.build-matrix.outputs.matrix }}
      issue-url: ${{ steps.check-existing-issue.outputs.issue-url || steps.create-tracking-issue.outputs.issue-url }}
      linear-access-token: ${{ steps.linear-auth.outputs.access-token }}
```

And update `create-sub-tracking-issues`:
```yaml
  create-sub-tracking-issues:
    name: Create sub-issue for tracking ${{ matrix.version }} release
    needs: [create-parent-tracking-issue]
    strategy:
      matrix: ${{ fromJSON(needs.create-parent-tracking-issue.outputs.sub-issues-matrix) }}
    uses: ./.github/workflows/release-create-tracking-issue.yml
    with:
      version: ${{ matrix.version }}
      release-date: ${{ matrix.releaseDate }}
      skip-branch-validation: true
    secrets:
      LINEAR_ACCESS_TOKEN: ${{ needs.create-parent-tracking-issue.outputs.linear-access-token }}
      LINEAR_TEAM_ID: ${{ secrets.LINEAR_TEAM_ID }}
```

**Step 4: Update workflow permissions**

The `create-parent-tracking-issue` job needs a checkout step already present. No extra permissions needed since the `GH_SECRETS_TOKEN` PAT handles secret writes.

**Step 5: Commit**

```bash
git add .github/workflows/release-assignment.yml
git commit -m "ci: migrate release-assignment to Linear OAuth refresh tokens"
```

---

## Task 3: Update `release-create-tracking-issue.yml` to Accept Access Token

**Files:**
- Modify: `.github/workflows/release-create-tracking-issue.yml`

**Step 1: Update the secrets declaration to accept the access token**

Change the secrets block at the top:
```yaml
    secrets:
      LINEAR_ACCESS_TOKEN:
        required: true
      LINEAR_TEAM_ID:
        required: true
```

Also add support for `workflow_dispatch` (manual runs) which need their own auth. Add a new job or conditional auth step for manual triggers:

For `workflow_dispatch`, add secrets for client credentials + refresh token:
```yaml
  workflow_dispatch:
    inputs:
      version:
        description: 'Version (e.g., 9.5.0-beta.1, 9.5.0-rc.1, 9.5.0)'
        required: true
        type: string
      release-date:
        description: 'Release date (YYYY-MM-DD), defaults to today'
        required: false
        type: string
```

Add a conditional auth step at the start of the job that only runs for `workflow_dispatch`:
```yaml
      - name: Refresh Linear OAuth token (manual trigger only)
        if: ${{ github.event_name == 'workflow_dispatch' }}
        id: linear-auth
        uses: ./.github/actions/linear-auth
        with:
          linear-client-id: ${{ secrets.LINEAR_CLIENT_ID }}
          linear-client-secret: ${{ secrets.LINEAR_CLIENT_SECRET }}
          linear-refresh-token: ${{ secrets.LINEAR_REFRESH_TOKEN }}
          gh-secrets-token: ${{ secrets.GH_SECRETS_TOKEN }}
```

Then resolve the token to use based on trigger type:
```yaml
      - name: Resolve Linear access token
        id: resolve-token
        shell: bash
        run: |
          if [ "${{ github.event_name }}" = "workflow_dispatch" ]; then
            echo "token=${{ steps.linear-auth.outputs.access-token }}" >> "$GITHUB_OUTPUT"
          else
            echo "token=${{ secrets.LINEAR_ACCESS_TOKEN }}" >> "$GITHUB_OUTPUT"
          fi
```

**Step 2: Replace all `apiKey` usages with `accessToken`**

In every `LinearClient` instantiation, change:
```javascript
const linearClient = new LinearClient( {
  apiKey: '${{ secrets.LINEAR_OAUTH_TOKEN }}'
} );
```
to:
```javascript
const linearClient = new LinearClient( {
  accessToken: '${{ steps.resolve-token.outputs.token }}'
} );
```

There are 4 instantiations in this workflow.

**Step 3: Commit**

```bash
git add .github/workflows/release-create-tracking-issue.yml
git commit -m "ci: migrate release-create-tracking-issue to Linear OAuth refresh tokens"
```

---

## Task 4: Test the Migration

**Step 1: Run the initial token migration (manual, requires admin)**

Execute the migration curl command from Task 0, Step 3. Store the resulting `refresh_token` as the `LINEAR_REFRESH_TOKEN` GitHub secret.

**Step 2: Test via `workflow_dispatch`**

Trigger `release-create-tracking-issue.yml` manually with a test version to verify:
- The token refresh step succeeds
- The Linear API calls work with the new short-lived token
- The `LINEAR_REFRESH_TOKEN` secret is updated

**Step 3: Verify the rotated token works**

Trigger the workflow a second time to confirm the rotated refresh token (stored in the previous run) still works.

**Step 4: Test the full flow**

Trigger `release-assignment.yml` via `workflow_dispatch` to test the complete chain including sub-issue creation.

---

## Task 5: Cleanup

**Step 1: Remove the old `LINEAR_OAUTH_TOKEN` secret**

After confirming everything works, delete the `LINEAR_OAUTH_TOKEN` secret from GitHub repo settings.

**Step 2: Enable refresh tokens in Linear OAuth app settings**

If not already done in Task 0, enable refresh tokens in the Linear OAuth app settings for "GitHub WooCommerce workflow".

**Step 3: Update any documentation**

If there are internal docs referencing `LINEAR_OAUTH_TOKEN`, update them to reflect the new secrets setup.

**Step 4: Final commit (if any doc changes)**

```bash
git add -A
git commit -m "ci: clean up old Linear OAuth token references"
```

---

## Risk Mitigations

1. **Concurrent workflow runs**: If two workflow runs refresh the token simultaneously, one will have a stale refresh token. Mitigation: The `release-assignment.yml` workflow does auth once and passes the access token to sub-workflows, so only one refresh per workflow execution.

2. **Token refresh failure**: If the refresh fails (e.g., network issue), the workflow fails early with a clear error message before any Linear operations are attempted.

3. **Secret update failure**: If the GitHub API call to update the secret fails, the access token is still valid for the current run. The refresh token in the response is lost, so the next run will also fail. Mitigation: The workflow step logs a clear failure, and the original refresh token can be manually re-migrated using `/oauth/migrate_old_token` if needed (as long as the old token hasn't been fully revoked yet).

4. **Rollback**: Keep the `LINEAR_OAUTH_TOKEN` secret until the new flow is verified. The old token remains valid until April 1, 2026.
