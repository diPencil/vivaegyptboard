#!/bin/bash

# Script to create a zip file with changes between the last 2 git tags
# Usage: ./create_update_zip.sh [branch_name]
# If branch_name is provided, it will use that branch's tag prefix instead of the current branch

set -e  # Exit on any error

# Function to print colored output
print_info() {
    echo -e "\033[1;34m[INFO]\033[0m $1"
}

print_success() {
    echo -e "\033[1;32m[SUCCESS]\033[0m $1"
}

print_error() {
    echo -e "\033[1;31m[ERROR]\033[0m $1"
}

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not in a git repository!"
    exit 1
fi

# Get branch name (from parameter or current branch)
if [ -n "$1" ]; then
    target_branch="$1"
    print_info "Using specified branch: $target_branch"
else
    target_branch=$(git branch --show-current)
    print_info "Using current branch: $target_branch"
fi

# Determine tag prefix based on branch
if [ "$target_branch" = "master" ]; then
    tag_prefix="w-"
elif [ "$target_branch" = "worksuite-saas" ]; then
    tag_prefix="ws-"
else
    print_error "Unsupported branch: $target_branch. Only 'master' and 'worksuite-saas' branches are supported."
    exit 1
fi

print_info "Looking for tags with prefix: $tag_prefix"

# Get the last 2 tags with the appropriate prefix (sorted by version)
print_info "Getting the last 2 git tags for branch $target_branch..."
tags=($(git tag --sort=-version:refname | grep "^$tag_prefix" | head -2))

if [ ${#tags[@]} -lt 2 ]; then
    print_error "Need at least 2 tags with prefix '$tag_prefix' in the repository!"
    print_info "Available tags with this prefix:"
    git tag --sort=-version:refname | grep "^$tag_prefix" || echo "  None found"
    exit 1
fi

newer_tag=${tags[0]}
older_tag=${tags[1]}

print_info "Newer tag: $newer_tag"
print_info "Older tag: $older_tag"

# Get the commit hashes for the tags
newer_commit=$(git rev-list -n 1 $newer_tag)
older_commit=$(git rev-list -n 1 $older_tag)

print_info "Newer commit: $newer_commit"
print_info "Older commit: $older_commit"

# Create output filename with tag names
output_file="update_${older_tag}_to_${newer_tag}.zip"

print_info "Creating zip file: $output_file"

# Get the list of changed files and filter out files that don't exist at HEAD
all_changed_files=$(git diff --name-only --diff-filter=d $older_commit $newer_commit)

if [ -z "$all_changed_files" ]; then
    print_error "No files changed between $older_tag and $newer_tag"
    exit 1
fi

# Filter files that exist at HEAD
existing_files=""
while IFS= read -r file; do
    if git cat-file -e HEAD:"$file" 2>/dev/null; then
        if [ -z "$existing_files" ]; then
            existing_files="$file"
        else
            existing_files="$existing_files"$'\n'"$file"
        fi
    else
        print_info "Skipping file (doesn't exist at HEAD): $file"
    fi
done <<< "$all_changed_files"

if [ -z "$existing_files" ]; then
    print_error "No existing files to archive between $older_tag and $newer_tag"
    exit 1
fi

print_info "Files to include in zip:"
echo "$existing_files" | sed 's/^/  - /'

# Create the zip archive
git archive --output="$output_file" HEAD $existing_files

print_success "Created $output_file with changes between $older_tag and $newer_tag"
print_info "Archive contains $(echo "$existing_files" | wc -l) files"
