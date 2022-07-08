# LaravelReplaceString

LaravelReplaceString - Command to replace the contents of a specified file with a specific strings.

### Install

1. Write composer.json as an example and execute the command.

composer.json

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/yuki12321/laravel-replace-string"
    }
  ],
  "require-dev": {
    "yuki12321/laravel-replace-string": "dev-master"
  }
}
```

```bash
  composer install
```

### Usage

```
php artisan replace:string <subjectFilePath>
    [configFilePath]
    [suspendString]
    [resumeString]
    [--b|needBackup]
```

### Examples

#### For the simplest example, replace the contents of a specified file

1. Create a configuration file  
   Create a json file in an arbitrary location.  
   In this json file, list the characters to be searched/replaced.  
   This json file can be placed anywhere in the project.

```json
{
  "[% foo %]": "{{ $data->getFoo() }}",
  "[% bar %]": "{{ $data->getBar() }}",
  "[% baz %]": "{{ $data->getBaz() }}"
}
```

2. Execute command  
   Replaces strings in the specified file.  
   The first argument specifies the path to the file for character substitution.  
   The second argument specifies the path to the json file you just created. Specify the file path for the project, respectively. This completes the operation.

resources/views/foo/bar.blade.php

```plane
[% foo %]
[% bar %]
[% baz %]
```

```bash
  php artisan replace:string resources/views/foo/bar.blade.php baz.json
```

```plane
{{ $data->getFoo() }}
{{ $data->getBar() }}
{{ $data->getBaz() }}
```

#### A more advanced example, replace the contents of a specified file

- Some characters can also be processed without replacement.  
  The third argument of the command specifies the string to start interrupting the substitution.  
  The fourth argument of the command specifies the string to resume replacement.  
  If the command is executed after enclosing strings in the file to be processed with the strings specified in the third and fourth arguments, only the enclosed strings will not be replaced.  
  (When describing in the file to be processed, please start a new line before and after the string to suspend/resume substitution.)

resources/views/foo/bar.blade.php

```plane
[% foo %]
START
[% bar %]
END
[% baz %]
```

```bash
    php artisan replace:string resources/views/foo/bar.blade.php baz.json START END
```

```plane
{{ $data->getFoo() }}
START
[% bar %]
END
{{ $data->getBaz() }}
```

- When described in dotenv, the command can always be executed without specifying optional arguments.  
  Specifying the second through fourth arguments in a description to dotenv allows the command to be executed without specifying optional arguments.

```yaml
REPLACE_STRING_COMMAND_CONFIG_FILE_RELATIVE_PATH='test2.json'
REPLACE_STRING_COMMAND_SUSPEND_STRING='START'
REPLACE_STRING_COMMAND_RESUME_STRING='END'
```

- You can keep a backup of the file to be processed before replacement.  
  The `-b` option can be used to leave backup files (.bak) in the same directory as before replacement.

```bash
    php artisan replace:string resources/views/foo/bar.blade.php baz.json -b
```

```plane
.
└── resources
    └── views
        └── foo
          └── bar.blade.php
          └── bar.blade.php.bak
```
