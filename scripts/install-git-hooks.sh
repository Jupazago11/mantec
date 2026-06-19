#!/usr/bin/env bash
set -euo pipefail

git config core.hooksPath .githooks
echo "Hooks del repositorio activados en .githooks"
