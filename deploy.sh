#!/bin/bash

# Main config
DIR=$(pwd)
PLUGINSLUG=$(basename "$DIR")
MAINFILE="$PLUGINSLUG.php"
# SVN user
SVNUSER=""
# Verbose mode
VERBOSE=false
# Deploy mode
DEPLOY=false
# Skip assets
SKIP_ASSETS=false
# Script name
SCRIPT_NAME=$(basename "$0")
# Git branch
GITBRANCH="main"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        -d|--deploy)
            DEPLOY=true
            shift
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -sa|--skip-assets)
            SKIP_ASSETS=true
            shift
            ;;
        -b|--branch)
            if [[ -z "$2" || "$2" == -* ]]; then
                echo "Error: Missing value for --branch option."
                exit 1
            fi
            GITBRANCH="$2"
            shift 2
            ;;
        -mf|--mainfile)
            if [[ -z "$2" || "$2" == -* ]]; then
                echo "Error: Missing value for --mainfile option."
                exit 1
            fi
            MAINFILE="$2"
            shift 2
            ;;
        -p|--path)
            if [[ -z "$2" || "$2" == -* ]]; then
                echo "Error: Missing value for --path option."
                exit 1
            fi
            DIR="$2"
            shift 2
            ;;
        -s|--slug)
            if [[ -z "$2" || "$2" == -* ]]; then
                echo "Error: Missing value for --slug option."
                exit 1
            fi
            PLUGINSLUG="$2"
            shift 2
            ;;
        -h|--help)
            echo "Deploy WordPress plugin to the official repository."
            echo "Dry-run mode is enabled by default. Use -d or --deploy to deploy."
            echo "Usage: ./$SCRIPT_NAME <svn-user> [-d|--deploy] [-v|--verbose] [-sa|--skip-assets] [-b|--branch <branch>] [-mf|--mainfile <mainfile.php>] [-p|--path <plugin-path>] [-s|--slug <plugin-slug>]"
            exit 0
            ;;
        *)
            # Assume the first non-flag argument is SVNUSER
            if [[ -z "$SVNUSER" ]]; then
                SVNUSER="$1"
                shift
            else
                echo "Error: Unexpected argument '$1'."
                exit 1
            fi
            ;;
    esac
done

# Validate required parameter
if [[ -z "$SVNUSER" ]]; then
    echo "Error: Missing required parameter <svn-user>."
    echo "Usage: ./$SCRIPT_NAME <svn-user> [-d|--deploy] [-v|--verbose] [-sa|--skip-assets] [-b|--branch <branch>] [-mf|--mainfile <mainfile.php>] [-p|--path <plugin-path>] [-s|--slug <plugin-slug>]"
    exit 1
fi

# Debug output (optional, for testing purposes)
if [[ "$VERBOSE" == true ]]; then
    echo "SVNUSER: $SVNUSER"
    echo "DEPLOY: $DEPLOY"
    echo "VERBOSE: $VERBOSE"
    echo "SKIP_ASSETS: $SKIP_ASSETS"
    echo "GITBRANCH: $GITBRANCH"
    echo "MAINFILE: $MAINFILE"
    echo "DIR: $DIR"
    echo "PLUGINSLUG: $PLUGINSLUG"
fi

# Git config
GITPATH="$DIR/"
# SVN config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on wordpress.org, with no trailing slash

# Function to execute or echo commands based on deploy mode
execute_or_echo() {
    local command="$1"
    shift
    local args=("$@")

    # Determine the command type (e.g., git, svn)
    case "$command" in
        git)
            # In dry-run mode, allow all git commands except commit, tag, push
            if ! $DEPLOY && [[ "${args[0]}" == "commit" || "${args[0]}" == "tag" || "${args[0]}" == "push" ]]; then
                echo "[DRY-RUN] $command ${args[*]}"
            else
                if $VERBOSE; then
                    echo "$command ${args[*]}"
                fi
                "$command" "${args[@]}"
            fi
            ;;
        svn)
            # In dry-run mode, allow all svn commands except commit
            if ! $DEPLOY && [[ "${args[0]}" == "commit" ]]; then
                echo "[DRY-RUN] $command ${args[*]}"
            else
                if $VERBOSE; then
                    echo "$command ${args[*]}"
                fi
                "$command" "${args[@]}"
            fi
            ;;
        gh)
        # In dry-run mode, disallow all gh commands
            if ! $DEPLOY; then
                echo "[DRY-RUN] gh ${args[*]}"
            else
                if $VERBOSE; then
                    echo "gh ${args[*]}"
                fi
                "$command" "${args[@]}"
            fi
            ;;
        *)
        # For other commands, actually execute them
            if $VERBOSE; then
                echo "$command ${args[*]}"
            fi
            "$command" "${args[@]}"
        ;;
    esac
}

echo ".........................................."
echo ""

if $DEPLOY; then
    echo "Deployment"
else
    echo "Dry-run - use --deploy to deploy"
fi

echo ""
echo ".........................................."
echo ""

# Check if subversion is installed before running
if ! which svn >/dev/null; then
    echo "Command 'svn' not found. Exiting."
    exit 1
fi

# Check version in readme.txt is the same as plugin file
NEWVERSION1=$(grep "^Stable tag:" "$GITPATH"/readme.txt | awk -F' ' '{print $NF}')
NEWVERSION2=$(grep -E "^[[:space:]]*\*?[[:space:]]*Version:" "$GITPATH"/"$MAINFILE" | awk -F' ' '{print $NF}')
echo "readme.txt version: $NEWVERSION1"
echo "$MAINFILE version: $NEWVERSION2"

# Check if version in readme.txt & $MAINFILE don't match
if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then
    echo "Version in readme.txt & $MAINFILE don't match. Exiting."
    exit 1
fi

# Check if git tag exists
if git show-ref --tags --quiet --verify -- "refs/tags/$NEWVERSION1"; then
    echo "Version $NEWVERSION1 already exists as git tag. Exiting."
    exit 1
fi

# Prompt for commit message
echo -e "Enter a commit message for this new version: \c"
read -r COMMITMSG

# Check that GITBRANCH exists
if ! git show-ref --verify --quiet "refs/heads/$GITBRANCH"; then
    echo "Branch $GITBRANCH does not exist. Exiting."
    exit 1
fi

# If gh cli is installed, check if user is authenticated
if which gh > /dev/null; then
    # If the user is not authenticated, instruct to run 'gh auth login' and exit
    if ! gh auth status > /dev/null 2>&1; then
        echo "You are not authenticated. Please run 'gh auth login'. Exiting."
        exit 1
    fi
fi

# Keep the current branch so that we can switch back to it later
CURRENTBRANCH=$(git rev-parse --abbrev-ref HEAD)

# Switch to $GITBRANCH branch
execute_or_echo git checkout "$GITBRANCH"

# Commit changes
execute_or_echo git commit -am "$COMMITMSG"

# Tag new version in git
execute_or_echo git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

# Push changes to origin
execute_or_echo git push origin "$GITBRANCH"
execute_or_echo git push origin "$GITBRANCH" --tags

# check if gh cli is installed
if which gh > /dev/null; then
    # Create a GitHub release using the GitHub API
    echo "Creating GitHub release for tag $NEWVERSION1..."

    # Define the GitHub repository owner and name
    GITHUB_OWNER=$(git config --get remote.origin.url | sed -E 's#(https://github.com|git@github.com:)([^/]+)/.*#\2#')
    GITHUB_REPO=$(git config --get remote.origin.url | sed -E 's#(https://github.com|git@github.com:)[^/]+/([^/]+).git#\2#')
    # Define the release path
    RELEASEPATH="/tmp/$PLUGINSLUG-release/"
    # Define the zip file name
    ZIPFILE="$PLUGINSLUG.zip"

    # Delete the release path if it doesn't exist
    if [ -d "$RELEASEPATH" ]; then
        execute_or_echo rm -fr "$RELEASEPATH"
    fi

    # Create the release path
    execute_or_echo mkdir -p "$RELEASEPATH"

    # Create a zip file of the plugin, excluding all hidden files and *.sh files
    execute_or_echo rsync -r --exclude=".*" --exclude="*.sh" "$GITPATH" "$RELEASEPATH"

    # Use tar to create the zip file
    execute_or_echo tar -czf "/tmp/$ZIPFILE" -C "$RELEASEPATH" .

    # Delete the release path
    execute_or_echo rm -fr "$RELEASEPATH"

    # use gh cli to create a release
    execute_or_echo gh release create v"$NEWVERSION1" \
        --title "Release v$NEWVERSION1" \
        --notes "Auto-deployed from tag $NEWVERSION1" \
        --repo "$GITHUB_OWNER/$GITHUB_REPO" \
        "/tmp/$ZIPFILE"

    # Delete the zip file
   execute_or_echo rm -f "/tmp/$ZIPFILE"
else
    echo "Command 'gh' not found. Skipping GitHub release creation."
fi

# Delete the local SVN repo if it exists
if [ -d "$SVNPATH" ]; then
    execute_or_echo rm -fr "$SVNPATH"
fi

# Create local copy of SVN repo
execute_or_echo svn co "$SVNURL" "$SVNPATH"

#Init directories assets, tags, trunk if they do not exist
if [ ! -d "$SVNPATH"/assets ]; then
    execute_or_echo mkdir "$SVNPATH"/assets
fi

if [ ! -d "$SVNPATH"/tags ]; then
    execute_or_echo mkdir "$SVNPATH"/tags
fi

if [ ! -d "$SVNPATH"/trunk ]; then
    execute_or_echo mkdir "$SVNPATH"/trunk
fi

# Clear SVN repo trunk only if it is not empty
if [ -n "$(ls -A "$SVNPATH"/trunk/ 2>/dev/null)" ]; then
    execute_or_echo svn rm "$SVNPATH"/trunk/*
else
    echo "Trunk is already empty. Skipping removal."
fi

# Export HEAD of branch from git to SVN trunk
execute_or_echo git checkout-index -a -f --prefix="$SVNPATH"/trunk/

# Ignore GitHub-specific files
execute_or_echo svn propset svn:ignore "deploy.sh
.DS_Store
.vscode
.git
.gitignore" "$SVNPATH/trunk/"

# Add only readme.txt to SVN trunk
execute_or_echo cd "$SVNPATH"/trunk/
execute_or_echo svn add readme.txt

# Create new SVN tag
execute_or_echo cd "$SVNPATH"
execute_or_echo svn copy trunk/ tags/"$NEWVERSION1"/

# Add all new files in the tag folder
execute_or_echo cd "$SVNPATH"/tags/"$NEWVERSION1"
execute_or_echo bash -c "
    svn status |
    grep '^?' |
    awk '{print \$2}' |
    xargs -I {} svn add {}
"

if ! $SKIP_ASSETS; then
    # Change to the assets folder
    execute_or_echo cd "$SVNPATH"/assets/

    # Pause the script until the user presses a key
    echo "Adjust assets in $SVNPATH/assets/ and press any key to continue..."
    read -rn 1 -s

    # Add all new files in the assets folder
    execute_or_echo bash -c "
        svn status |
        grep '^?' |
        awk '{print \$2}' |
        xargs -I {} svn add {}
    "
fi

# Commit trunk, tag and assets changes in one step
execute_or_echo cd "$SVNPATH"
execute_or_echo svn commit --username="$SVNUSER" -m "$COMMITMSG"

# Go back to current directory
execute_or_echo cd "$GITPATH"

# Clean up temporary directory
execute_or_echo rm -fr "${SVNPATH:?}/"

# Switch back to the original branch
execute_or_echo git checkout "$CURRENTBRANCH"

if ! $DEPLOY; then
    echo "*** Dry-run complete. No changes were made. ***"
else
    echo "*** Deployment complete. ***"
fi

echo ""