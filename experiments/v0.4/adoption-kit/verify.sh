#!/usr/bin/env sh
set -eu

if [ "$#" -ne 1 ]; then
  echo "usage: ./verify.sh https://example.com" >&2
  exit 2
fi

origin="${1%/}"

echo "----- robots.txt -----"
curl -fsS "$origin/robots.txt"
echo
echo "----- HUMIA manifest -----"
curl -fsS "$origin/.well-known/humia.json"
echo
echo
echo "Semantic checker:"
echo "python3 experiments/v0.4/check_live.py $origin"
