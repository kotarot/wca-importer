# wca-importer

wca-importer is a command-line support for importing [WCA database](https://www.worldcubeassociation.org/results/misc/export.html).
You can use it on the terminal or in the crontab.


## Usage

Firstly, rename `config.sample.json` to `config.json` and edit `config.json` to set your database configurations.

### PHP

```
php -f import.php
```


## Future work

Only PHP implementation is completed so far.
I will implement this tool in other languages such as Python, node.js, etc.
Of course, your PR is always welcome.


## Circle CI

https://circleci.com/gh/kotarot/wca-importer
