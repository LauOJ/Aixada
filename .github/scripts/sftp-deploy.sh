#!/bin/bash
set -e

BEFORE=$1
CURRENT=$2
REMOTE_PATH=$3
SFTP_USER=$4
SFTP_HOST=$5
SFTP_PASSWORD=$6

EXCLUDES=("local_config/config.php" "local_config/debug_mail" "local_config/export" "local_config/custom_img" ".git" ".github")

is_excluded() {
  local file=$1
  for pattern in "${EXCLUDES[@]}"; do
    [[ "$file" == "$pattern"* ]] && return 0
  done
  return 1
}

# First deploy or no history: full mirror
if [ -z "$BEFORE" ] || [ "$BEFORE" = "0000000000000000000000000000000000000000" ]; then
  echo "First deploy — full mirror"
  cat > /tmp/deploy.lftp << LFTP
set sftp:auto-confirm yes
set net:timeout 30
open -u ${SFTP_USER},${SFTP_PASSWORD} sftp://${SFTP_HOST}
mirror --reverse --delete --verbose --exclude-glob .git/ --exclude-glob .github/ --exclude-glob local_config/config.php --exclude-glob local_config/debug_mail/ --exclude-glob local_config/export/ --exclude-glob local_config/custom_img/ . ${REMOTE_PATH}/
bye
LFTP
  lftp -f /tmp/deploy.lftp
  exit 0
fi

echo "Incremental deploy: $BEFORE -> $CURRENT"

CHANGED=$(git diff --name-only --diff-filter=ACM "$BEFORE" "$CURRENT" 2>/dev/null || true)
DELETED=$(git diff --name-only --diff-filter=D  "$BEFORE" "$CURRENT" 2>/dev/null || true)

echo "Changed: $(echo "$CHANGED" | grep -c . || true) files"
echo "Deleted: $(echo "$DELETED" | grep -c . || true) files"

{
  echo "set sftp:auto-confirm yes"
  echo "set net:timeout 30"
  echo "set net:max-retries 3"
  echo "open -u ${SFTP_USER},${SFTP_PASSWORD} sftp://${SFTP_HOST}"

  # Create directories (ignore errors if already exist)
  echo "set cmd:fail-exit false"
  while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && continue
    dir=$(dirname "$file")
    [ "$dir" != "." ] && echo "mkdir ${REMOTE_PATH}/${dir}"
  done <<< "$CHANGED"
  echo "set cmd:fail-exit true"

  # Upload changed files
  while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && { echo "# skipped: $file"; continue; }
    echo "put ${file} -o ${REMOTE_PATH}/${file}"
  done <<< "$CHANGED"

  # Delete removed files
  while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && continue
    echo "set cmd:fail-exit false"
    echo "rm ${REMOTE_PATH}/${file}"
    echo "set cmd:fail-exit true"
  done <<< "$DELETED"

  echo "bye"
} > /tmp/deploy.lftp

echo "=== SFTP script ==="
cat /tmp/deploy.lftp
echo "==================="

lftp -f /tmp/deploy.lftp
