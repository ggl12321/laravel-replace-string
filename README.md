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

### Examples

#### For the simplest example, replace the contents of a specified file

1. Creating a configuration file.
   Create a json file anywhere.
   List the characters to search for and replace in this json file.
   This file can be placed freely within the project.

```json
{
  "[% foo %]": "{{ $data->getFoo() }}",
  "[% bar %]": "{{ $data->getBar() }}",
  "[% foobar %]": "{{ $data->getFoobar() }}"
}
```

2. Execute the command
   Replace strings in the specified file.
   The first argument specifies the path to the file for character substitution.
   The second argument is the path to the json file you just created. Specify the file path for your project, respectively.The operation is now complete.

resources/views/foo/bar.blade.php

```plane
[% foo %]
[% bar %]
[% foobar %]
```

```bash
  php artisan replace:string resources/views/foo/bar.blade.php foobar.json
```

```plane
{{ $data->getFoo() }}
{{ $data->getBar() }}
{{ $data->getFoobar() }}
```

#### A more advanced example, replace the contents of a specified file

- The process can be performed without substituting some characters.
  The third argument of the command specifies the start string of the replacement interruption. The fourth argument of the command specifies the start string for resuming substitution.
  If you enclose a string in the target file with START and END and then execute the command, only the enclosed string will not be replaced!

resources/views/foo/bar.blade.php

```plane
[% foo %]
START
[% bar %]
END
[% foobar %]
```

```bash
    php artisan replace:string resources/views/foo/bar.blade.php foobar.json START END
```

```plane
{{ $data->getFoo() }}
START
[% bar %]
END
{{ $data->getFoobar() }}
```

- You can specify arguments to dotenv without specifying optional arguments.
  Adding the following description to dotenv will execute the command without specifying the second through fourth arguments.

```yaml
REPLACE_STRING_COMMAND_CONFIG_FILE_RELATIVE_PATH='test2.json'
REPLACE_STRING_COMMAND_SUSPEND_STRING='START'
REPLACE_STRING_COMMAND_RESUME_STRING='END'
```

- You can keep the file before replacement as a backup.
  The `-b` option allows you to leave the backup file (.bak) in the same directory as it was before the replacement was performed

```bash
    php artisan replace:string resources/views/foo/bar.blade.php foobar.json -b
```

```plane
.
└── resources
    └── views
        └── foo
          └── bar.blade.php
          └── bar.blade.php.bak
```
