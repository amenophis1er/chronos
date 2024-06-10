#!/bin/sh
set -e

REPO="amenophis1er/chronos"
PHAR_NAME="chronos.phar"
CHECKSUM_FILE="SHA256SUMS"
INSTALL_DIR="/usr/local/bin"
INSTALL_PATH="$INSTALL_DIR/chronos"

# Download the latest PHAR file
echo "Downloading $PHAR_NAME..."
curl -L -o $PHAR_NAME "https://github.com/$REPO/releases/latest/download/$PHAR_NAME"

# Download the checksum file
echo "Downloading checksum file..."
curl -L -o $CHECKSUM_FILE "https://github.com/$REPO/releases/latest/download/$CHECKSUM_FILE"

# Verify the checksum
echo "Verifying checksum..."
grep "$PHAR_NAME" $CHECKSUM_FILE | sha256sum -c - || {
    echo "Checksum verification failed! The downloaded file may be corrupted."
    rm -f $PHAR_NAME $CHECKSUM_FILE
    exit 1
}

# Move it to the installation directory
echo "Installing $PHAR_NAME..."
mv $PHAR_NAME $INSTALL_PATH

# Make it executable
chmod +x $INSTALL_PATH

# Clean up
rm -f $CHECKSUM_FILE

echo "chronos installed successfully at $INSTALL_PATH"
