
## FileSystemOps JSON (FSOps)

```json
[
   {
      "path": "src/App.js",
      "action": "create",
      "type": "file",
      "content": "Here goes the full content, well escaped to avoid any parsing error.",
      "permissions": "0600"
   },
   {
      "path": "src/components",
      "action": "create",
      "type": "directory",
      "permissions": "0755"
   },
   {
      "path": "src/oldFile.js",
      "action": "delete",
      "type": "file"
   },
   {
      "path": "src/oldDirectory",
      "action": "delete",
      "type": "directory"
   }
]
```

## Technical Documentation

### 1. Overview

This FileSystemOps JSON (FSOps) format describes operations on a file system. Each operation is represented by a JSON object containing information about the path, action, type, content, and permissions. The JSON structure supports both file and directory operations, including creation and deletion.

### 2. Fields

#### `path`

- **Description**: Specifies the location of the file or directory in the file system.
- **Type**: `string`
- **Example**: `"src/App.js"`

#### `action`

- **Description**: Defines the type of operation to be performed.
- **Type**: `string`
- **Allowed Values**: `"create"`, `"delete"`
- **Example**: `"create"`

#### `type`

- **Description**: Indicates whether the operation targets a file or a directory.
- **Type**: `string`
- **Allowed Values**: `"file"`, `"directory"`
- **Example**: `"file"`

#### `content`

- **Description**: Contains the content to be written to the file. This field is mandatory if the action is `"create"` and the type is `"file"`.
- **Type**: `string`
- **Example**: `"console.log('Hello, World!');"`

#### `permissions`

- **Description**: Specifies the file or directory permissions in octal format. This field is optional for `delete` actions.
- **Type**: `string`
- **Format**: Unix-like permission representation.
- **Example**: `"0644"`

### 3. Action Definitions

#### `create`

- **Description**: Creates a new file or directory at the specified path.
- **Required Fields**: `path`, `type`, `permissions`
    - If `type` is `"file"`, `content` is also required.
- **Optional Fields**: None

#### `delete`

- **Description**: Deletes the file or directory at the specified path.
- **Required Fields**: `path`, `type`
- **Optional Fields**: None

### 4. Field Constraints

- **Path**: Must be a valid relative or absolute path. Avoid special characters that may cause issues in file systems.
- **Action**: Must be either `"create"` or `"delete"`.
- **Type**: Must be either `"file"` or `"directory"`.
- **Content**: Must be a valid string if the action is `create` and the type is `file`.
- **Permissions**: Should be a valid Unix permission string, such as `"0644"`. Not required for `delete` actions but recommended for clarity.

### 5. JSON Schema

Here's a JSON Schema to validate the structure:

```json
{
   "$schema": "http://json-schema.org/draft-07/schema#",
   "type": "array",
   "items": {
      "type": "object",
      "properties": {
         "path": {
            "type": "string",
            "minLength": 1
         },
         "action": {
            "type": "string",
            "enum": ["create", "delete"]
         },
         "type": {
            "type": "string",
            "enum": ["file", "directory"]
         },
         "content": {
            "type": "string"
         },
         "permissions": {
            "type": "string",
            "pattern": "^[0-7]{3,4}$"
         }
      },
      "required": ["path", "action", "type"],
      "if": {
         "properties": { "action": { "const": "create" }, "type": { "const": "file" } }
      },
      "then": { "required": ["content", "permissions"] },
      "else": {
         "if": { "properties": { "action": { "const": "create" }, "type": { "const": "directory" } } },
         "then": { "required": ["permissions"] }
      }
   }
}
```

### 6. Examples

#### Create a File

```json
{
   "path": "src/App.js",
   "action": "create",
   "type": "file",
   "content": "console.log('Hello, World!');",
   "permissions": "0644"
}
```

#### Create a Directory

```json
{
   "path": "src/components",
   "action": "create",
   "type": "directory",
   "permissions": "0755"
}
```

#### Delete a File

```json
{
   "path": "src/oldFile.js",
   "action": "delete",
   "type": "file"
}
```

#### Delete a Directory

```json
{
   "path": "src/oldDirectory",
   "action": "delete",
   "type": "directory"
}
```

### 7. Notes

- Ensure that paths do not contain invalid characters for the target file system.
- Use proper escaping for content to avoid parsing errors.
- Permissions should reflect the intended use and security requirements.
