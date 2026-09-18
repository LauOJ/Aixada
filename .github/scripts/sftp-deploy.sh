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

# Unique parent directories that need to exist (excluding root ".")
DIRS=$(while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && continue
    dir=$(dirname "$file")
    [ "$dir" != "." ] && echo "$dir"
  done <<< "$CHANGED" | sort -u)

{
  echo "set sftp:auto-confirm yes"
  echo "set net:timeout 30"
  echo "set net:max-retries 3"
  echo "open -u ${SFTP_USER},${SFTP_PASSWORD} sftp://${SFTP_HOST}"

  # Create directories. mkdir returns "Access failed" when the dir already
  # exists, so tolerate errors here (fail-exit false) and chmod newly created
  # dirs so the web server can traverse/read them.
  echo "set cmd:fail-exit false"
  while IFS= read -r dir; do
    [ -z "$dir" ] && continue
    echo "mkdir -p ${REMOTE_PATH}/${dir}"
    echo "chmod 755 ${REMOTE_PATH}/${dir}"
  done <<< "$DIRS"

  # Upload changed files (real upload failures must fail the job)
  echo "set cmd:fail-exit true"
  while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && { echo "# skipped: $file"; continue; }
    echo "put ${file} -o ${REMOTE_PATH}/${file}"
  done <<< "$CHANGED"

  # Delete removed files
  echo "set cmd:fail-exit false"
  while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && continue
    echo "rm ${REMOTE_PATH}/${file}"
  done <<< "$DELETED"

  # Verification: list what actually landed on the server (visible in the log)
  echo "# === VERIFY DEPLOYED FILES ==="
  while IFS= read -r file; do
    [ -z "$file" ] && continue
    is_excluded "$file" && continue
    echo "cls -l ${REMOTE_PATH}/${file}"
  done <<< "$CHANGED"

  echo "bye"
} > /tmp/deploy.lftp

echo "=== SFTP script ==="
cat /tmp/deploy.lftp
echo "==================="

lftp -f /tmp/deploy.lftp
