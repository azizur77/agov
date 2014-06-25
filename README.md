# aGov [![Build Status](https://travis-ci.org/previousnext/agov.svg?branch=2.x)](https://travis-ci.org/previousnext/agov)

## Download

aGov is available as a full drupal site in tgz and zip format at: http://drupal.org/project/agov

## Building from Source

Source is available from GitHub at https://github.com/previousnext/agov

### Requirements

Install phing and drush in the standard way. You can use composer to install both
tools using the following:

```
composer global require --prefer-dist --no-interaction drush/drush:6.*
composer global require --prefer-dist --no-interaction phing/phing:2.7.*
```

### Building
To install a local working copy of aGov follow these steps.

First create a copy of build.properties and update it for your local settings.

```
cp build.example.properties build.properties
```

Run the following phing commands to build a site in a directory _at the same level_
as the current directory called `drupal`.

```
phing prepare:all
phing validate:all
phing make
phing site-install
phing login
```

You should point your apache vhost configuration to `drupal`.

### Developing

For front-end development tasks, you can run _gulp_ to compile sass etc.

To install node.js using home brew, do the following:

```
brew install node
```

To install gulp and plugins, type:

```
npm install
```

It will read from the package.json file and install into _node_modules_.

To run the default gulp task type:

```
gulp
```

### Testing

aGov uses behat for its functional tests. To run behat tests, use the following:

```
phing test:all
```
