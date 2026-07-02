#!/usr/bin/env bash
# Deploy TDA Currents to newsletter.tomahawkdestiny.com
# Additive push only (no --delete): never touches .well-known/, php.ini,
# .user.ini, .htaccess, or the server-written data/ and uploads/ folders.
set -euo pipefail
cd "$(dirname "$0")"

rsync -avz -e "ssh -p 1157 -i ~/.ssh/tda_deploy" \
  --exclude '.git/' --exclude '.claude/' --exclude '.htaccess' --exclude 'CLAUDE.md' --exclude 'deploy.sh' \
  --exclude 'reference/' --exclude 'icons/source/' --exclude 'data/' --exclude 'uploads/' \
  ./ tomahawk@tomahawkdestiny.com:/home/tomahawk/newsletter.tomahawkdestiny.com/

echo "Deploy complete."
