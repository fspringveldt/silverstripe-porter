## SilverStripe Porter

[![Total Downloads](https://poser.pugx.org/silverstripe/porter/downloads.svg)](https://packagist.org/packages/silverstripe/porter)

SilverStripe Porter (think kitchen porter) seeks to automate several menial tasks for the SilverStripe framework via cli.

Tasks currently supported:
* Creating a new module

## Installation
It is recommended to install globally using composer

```sh 
composer require global silverstripe/porter dev-master
```

After installation, create a symlink as follow (Note, adapt this command to your OS):

```sh
ln -s ~/.composer/vendor/silverstripe/porter/silverstripe ~/.composer/bin/silverstripe
```

Reloading your profile (`source ~/.bash_profile`) means you can now run `silverstripe` to view the help menu.

## Usage

### Creating a new module

The full command with all options is:

```sh
silverstripe create-module [--nonVendor] [--ss3] [--withTravisCI] [--withCircleCI] [--] <module-name> <module-namespace> [<module-path>]
```
* **--nonVendor** option adds `type: silverstripe-module` to your composer.json. This defaults to `type: silverstripe-vendormodule`
* **--ss3** option sets your module up with a base skeleton for an SilverStripe 3 module
* **--withTravisCI** adds minimal a travis.yml setup file
* **---withCircleCI** adds a minimal CircleCI config file to a .circleci folder

Example usage:
```
silverstripe create-module --withTravisCI foo/bar Foo\\\\Bar\\\\
```

## Bugtracker
Bugs are tracked on [github.com](https://github.com/fspringveldt/silverstripe-porter/issues).

## Development and Contribution
If you would like to make changes to this module, go on then, raise a PR via GitHub.

## Links
* [Bugtracker](https://github.com/fspringveldt/silverstripe-porter/issues)
* [License](./LICENSE)
 
## Credits
* Ingo Schommer - https://github.com/chillu
* Robbie Averill - https://github.com/robbieaverill
