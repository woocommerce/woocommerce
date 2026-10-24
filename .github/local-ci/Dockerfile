# The image `gh local-ci run --docker` cuts its containers from. The tool
# builds it from this file as committed, with this directory as the context,
# and rebuilds it only when something here changes. Keep the Node and pnpm
# versions in step with .nvmrc and the packageManager field in package.json.
#
# Every job gets a fresh container and only named volumes survive between
# them, so pnpm must be in the image itself: a setup step that installs it
# would be gone by the time the jobs start.
#
# The full Debian image, not -slim, because node-gyp needs python3, make and g++.
FROM node:24.15.0-bookworm@sha256:f22d6a1f082c02f292e86929b5b0442ac2e5eaf438a5dea9b1566601c3e05940

# The standalone pnpm, as pnpm/action-setup installs it in CI, rather than
# corepack, whose cache would sit under the build-time HOME and not the
# /home/local-ci volume the tool mounts at run time.
RUN npm install --global pnpm@10.33.0 && npm cache clean --force
