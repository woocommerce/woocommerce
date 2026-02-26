# WooCommerce Monorepo - Agent Instructions

See `CLAUDE.md` for project overview, architecture, and available skills.

## Cursor Cloud specific instructions

### System prerequisites

The VM snapshot has Node.js v20 (via nvm), PHP 8.1, Composer 2.x, Docker CE 28.x, and rsync pre-installed. The update script runs `pnpm install --frozen-lockfile` on each session start.

### Starting the dev environment

After `pnpm install`, you must build the monorepo before starting wp-env:

```sh
cd /workspace
pnpm build                              # builds all packages + client apps
cd plugins/woocommerce
pnpm env:dev                            # starts WordPress at localhost:8888 (dev) and localhost:8086 (tests)
```

**Docker must be running first.** If `dockerd` is not running:

```sh
sudo dockerd &>/tmp/dockerd.log &
sleep 3
sudo chmod 666 /var/run/docker.sock     # needed in nested-container environments
```

### wp-env credentials

- Dev site: `http://localhost:8888` - admin / password
- Test site: `http://localhost:8086` - admin / password

### Running tests

- **PHP unit tests** (run in Docker via wp-env): `pnpm test:php:env -- --filter TestClassName` (from `plugins/woocommerce/`)
- **JS/Jest tests**: `pnpm test:js -- <pattern>` (from `plugins/woocommerce/client/admin/`)
- See `CLAUDE.md` and `.claude/skills/woocommerce-dev-cycle/` for full test and lint commands.

### Gotchas

- The `lint:php:changes` command uses `[[` bash syntax but the default shell (`sh`) in composer scripts is dash. It exits 1 when there are no PHP changes; this is normal.
- `pnpm build` requires `rsync` (pre-installed in snapshot) for the copy-assets steps.
- The monorepo needs Node v20 (`engines.node: ^20.11.1`); nvm is configured with `default` alias pointing to v20. Source nvm before running pnpm: `export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm use 20`.
