#!/usr/bin/env php
<?php
function getShellConfigFile()
{
    $shell = getenv('SHELL');
    if (strpos($shell, 'zsh') !== false) {
        return getenv('HOME') . '/.zshrc';
    } else if (strpos($shell, 'bash') !== false) {
        return getenv('HOME') . '/.bashrc';
    } // Extend with other shells if necessary
    else {
        return null;
    }
}

function appendToConfigFile($configFile)
{
    $cmdPath      = __DIR__;
    $commandToAdd = <<<EOT

# Custom command for chronos
chronos() {
    local script_path="{$cmdPath}/chronos.php"
    if [[ -x "\$script_path" ]]; then
        "\$script_path" "\$@"
    else
        php "\$script_path" "\$@"
    fi
}

EOT;

    // Read the current contents of the file
    $contents = file_get_contents($configFile);

    // Remove existing chronos function if it exists
    $startMarker = "# Custom command for chronos";
    $endMarker   = "}\n";
    $startPos    = strpos($contents, $startMarker);
    if ($startPos !== false) {
        $endPos        = strpos($contents, $endMarker, $startPos);
        $functionBlock = substr($contents, $startPos, $endPos - $startPos + strlen($endMarker));
        $contents      = str_replace($functionBlock, '', $contents);
        file_put_contents($configFile, $contents);
        echo "Existing Chronos command removed from $configFile.\n";
    }

    // Append the new chronos function
    file_put_contents($configFile, $commandToAdd, FILE_APPEND);
    echo "New Chronos command added to $configFile. Please restart your shell or source the config file to apply changes.\n";
    echo "Please run the following command to apply the changes:\n";
    echo "source $configFile\n";

}


$configFile = getShellConfigFile();

if ($configFile) {
    appendToConfigFile($configFile);
} else {
    echo "Unsupported shell or unable to detect shell configuration file.\n";
}

