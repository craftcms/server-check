#!/bin/bash

# Craft CMS Server Check (one-liner)
#
# From the environment you want to check, run:
# `curl -Lsf https://raw.githubusercontent.com/craftcms/server-check/HEAD/check.sh | bash`

[[ $- = *i* ]] && echo "Don't source this script!" && return 10

checkTools() {
	Tools=("curl" "php" "rm" "tar" "grep" "cut")

	for tool in ${Tools[*]}; do
		if ! checkCmd $tool; then
			echo "Aborted, missing $tool."
			exit 6
		fi
	done
}

checkCmd() {
	command -v "$1" > /dev/null 2>&1
}

function serverCheck() {
  checkTools

  tmpDir="/tmp/craftcms-server-check"
  assetUrl="https://github.com/craftcms/server-check/releases/latest/download/craftcms-server-check.tar.gz"
  downloadToFile="${tmpDir}/craftcms-server-check.tar.gz"
  phpScript="${tmpDir}/checkit.php"

  echo "Downloading file… ${assetUrl}"
  mkdir "${tmpDir}"
  curl -fsSL "${assetUrl}" --output "${downloadToFile}"

  echo "Extracting…"
  tar -xzf "${downloadToFile}" -C "${tmpDir}"

  echo "Running Craft Server Check…"
  php $phpScript
  returnCode=$?

  rm -rf "${tmpDir}"

  return $returnCode
}

serverCheck
exit $?
