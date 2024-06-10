# Chronos

Chronos is a versatile CLI utility designed to manage and update your project files based on specified JSON instructions. It also includes a command to generate a comprehensive dump of your directory structure and file contents. Chronos aims to streamline project management and provide detailed context for large language models or other purposes.

## Features

- **Update Code**: Update files and directories based on the instructions specified in `instruct.json`.
- **Dump Directory**: Generate a dump of the current directory's structure and contents into a Markdown or custom file format.
- **Flexible Exclusions**: Exclude specific files or directories using patterns.

## Installation

### Automated Installation

You can install Chronos using a single command that handles the download and setup:

```bash
curl -sSL https://github.com/amenophis1er/chronos/releases/latest/download/install.sh | sh
```

### Manual Installation

Alternatively, you can clone the repository and install dependencies manually:

1. **Clone the Repository**:
    ```bash
    git clone https://github.com/yourusername/chronos.git
    cd chronos
    ```

2. **Install Dependencies**:
    ```bash
    composer install
    ```

3. **Set Up the Command**:
    ```bash
    ./bin/install_cmd.php
    ```

## Usage

Chronos provides various CLI commands to help manage your project files. Below are examples of how to use the key commands.

### Update Code

To update code based on the `instruct.json` file:

```bash
chronos gpt:update-code
```

### Dump Directory

To generate a dump of the current directory's structure and contents:

```bash
chronos gpt:dump
```

You can also specify additional exclusion patterns:

```bash
chronos gpt:dump --exclude='*.log,node_modules,vendor' --exclude='*.json'
```

## Commands

### `gpt:update-code`

Updates files and directories as specified in the `instruct.json` file located in the current directory.

#### `instruct.json` Example

```json
[
  {
    "path": "src/components/Auth",
    "action": "create",
    "type": "directory",
    "permissions": "0755"
  },
  {
    "path": "src/pages/LoginPage.js",
    "action": "create",
    "type": "file",
    "content": "import React from 'react';",
    "permissions": "0644"
  }
]
```

### `gpt:dump`

Generates a dump of the directory's structure and contents.

- **Options**:
    - `--exclude`: Specify patterns to exclude from the dump. Can be comma-separated or multiple options.
    - `--extension`: Set the file extension for the output file (default is `.md`).

Example:

```bash
chronos gpt:dump --exclude='*.log,node_modules,vendor' --extension=txt
```

## Configuration

### Environment Variables

Create a `.env` file in the project root to set environment variables. Example:

```
DEBUG=true
```

### Exclude Patterns

Patterns specified using the `--exclude` option can include:
- **Directories**: e.g., `node_modules`, `vendor`
- **Files**: e.g., `*.log`, `*.json`
- **Paths**: relative paths within the project

## Development

### Create a PHAR

To build the PHAR file manually:

```php
php -d phar.readonly=0 build/build-phar.php
```

## Contributing

We welcome contributions! Please follow these steps to contribute:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Make your changes.
4. Commit your changes (`git commit -am 'Add new feature'`).
5. Push to the branch (`git push origin feature-branch`).
6. Create a new Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.


