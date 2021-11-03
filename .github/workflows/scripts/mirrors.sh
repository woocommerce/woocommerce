echo "$BUILD_BASE is folder where the magic happens"
ls tmp/

if [[ "$GITHUB_REF" =~ ^refs/heads/ ]]; then
	BRANCH=${GITHUB_REF#refs/heads/}
else
	echo "::error::Could not determine branch name from $GITHUB_REF"
	exit 1
fi

printf "\n\n\e[7m Mirror: %s \e[0m\n" "${GIT_SLUG}"

CLONE_DIR="${BUILD_BASE}/${GIT_SLUG}"
echo "navigation to ${CLONE_DIR}"
cd "${CLONE_DIR}"

# Initialize the directory as a git repo, and set the remote
echo "Initializing with new branch: $BRANCH"
git init -b "$BRANCH" .
echo "Adding origin: https://github.com/${GIT_SLUG}"
git remote add origin "https://github.com/${GIT_SLUG}"
git config --local http.https://github.com/.extraheader "AUTHORIZATION: basic $(printf "x-access-token:%s" "$TOKEN" | base64)"