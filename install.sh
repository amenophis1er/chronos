#!/bin/sh
set -e

REPO="amenophis1er/chronos"
PHAR_NAME="chronos.phar"
CHECKSUM_FILE="SHA256SUMS"
SYSTEM_INSTALL_DIR="/usr/local/bin"
LOCAL_INSTALL_DIR="$HOME/bin"
USE_SUDO="false"

# Check if PHP is available
if ! command -v php >/dev/null 2>&1; then
    echo "Error: PHP is not installed. Please install PHP to use chronos."
    exit 1
fi

# Determine if sudo is available
if command -v sudo >/dev/null 2>&1; then
    USE_SUDO="true"
fi

# Determine the installation path
if [ "$USE_SUDO" = "true" ]; then
    INSTALL_PATH="$SYSTEM_INSTALL_DIR/chronos.phar"
    WRAPPER_PATH="$SYSTEM_INSTALL_DIR/chronos"
    SUDO_CMD="sudo"
else
    INSTALL_PATH="$LOCAL_INSTALL_DIR/chronos.phar"
    WRAPPER_PATH="$LOCAL_INSTALL_DIR/chronos"
    SUDO_CMD=""
    mkdir -p $LOCAL_INSTALL_DIR
    echo "No sudo access. Installing to $LOCAL_INSTALL_DIR. Ensure $LOCAL_INSTALL_DIR is in your PATH."
fi

# Download the latest PHAR file
echo "Downloading $PHAR_NAME..."
curl -L -o $PHAR_NAME "https://github.com/$REPO/releases/latest/download/$PHAR_NAME"

# Download the checksum file
echo "Downloading checksum file..."
curl -L -o $CHECKSUM_FILE "https://github.com/$REPO/releases/latest/download/$CHECKSUM_FILE"

# Check if sha256sum is available
if command -v sha256sum >/dev/null 2>&1; then
    # Verify the checksum
    echo "Verifying checksum..."
    if sha256sum -c $CHECKSUM_FILE --ignore-missing; then
        echo "Checksum verification passed."
    else
        echo "Checksum verification failed! The downloaded file may be corrupted."
        rm -f $PHAR_NAME $CHECKSUM_FILE
        exit 1
    fi
else
    echo "Warning: sha256sum not found. Skipping checksum verification."
fi

# Move the PHAR file to the installation directory
echo "Installing $PHAR_NAME to $INSTALL_PATH..."
$SUDO_CMD mv $PHAR_NAME $INSTALL_PATH

# Create a wrapper script
echo "Creating wrapper script at $WRAPPER_PATH..."
echo "#!/bin/sh\nphp \"$INSTALL_PATH\" \"\$@\"" | $SUDO_CMD tee $WRAPPER_PATH > /dev/null
$SUDO_CMD chmod +x $WRAPPER_PATH

# Clean up
rm -f $CHECKSUM_FILE

echo "chronos installed successfully at $WRAPPER_PATH"
