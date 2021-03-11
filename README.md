# m2devbox

The goal of m2devbox is to accelerate setting up development environment for Magento 2.
If you are developing a Magento 2 module and want to quickly test it in multiple release
versions of Magento, you can simply start your preferred Magento 2 release and your
module(s) will be automatically available inside it!

![m2devbox terminal demo animation](./docs/demo.gif)

## Requirements
* Docker 17.05+
* PHP ^7.4||^8.0 
* php-zlib

m2devbox can make use of the newer [BuildKit](https://www.docker.com/blog/advanced-dockerfiles-faster-builds-and-smaller-images-using-buildkit-and-multistage-builds/) 
Docker backend that results in a faster build process with smaller Docker images.
m2devbox automatically uses BuildKit if it finds a Docker Engine 18.09 or above.

## Installation

m2devbox is distributed in form of a PHAR file. You can use it locally in your project,
or you can install it globally.

### Global installation (recommended)
```shell
wget https://github.com/MageGyver/m2devbox/releases/download/0.2/m2devbox.phar
chmod u+x m2devbox.phar
mv m2devbox.phar /usr/local/bin/m2devbox
m2devbox status
```

Download the release `m2devbox.phar` to your local machine and move it to some 
directory that is accessible via `$PATH`. For moving, you might need superuser 
privileges (`sudo mv m2devbox.phar /usr/local/bin/m2devbox`).  
Now you can run `m2devbox` anywhere.

### Local installation
```shell
wget https://github.com/MageGyver/m2devbox/releases/download/0.2/m2devbox.phar
php m2devbox.phar status
```

Download the release `m2devbox.phar` to your local machine and use it right away.

## Usage

### Check currently running instances
```shell
m2devbox [status]
```

Running `m2devbox` without arguments or with the `status`argument displays what 
instances are currently running.

### Start an instance
```shell
m2devbox start <version>
```

To start an instance of Magento 2 simply provide the desired release version as 
an argument for `m2devbox start`.

If there does not exist an instance for this version yet, m2devbox automatically
starts downloading and installing it for you. You can grab a cup of coffee or
simply look at the progress indicator while m2devbox sets up everything for you.

If an instance of this version is already started, it will be stopped and restarted
again.

### Stop the currently running instance
```shell
m2devbox stop
```

This command stops the currently running Magento 2 instance.

### Clear an instance
```shell
m2devbox clear [<versions>]
```

This command stops the given instance(s) and deletes the associated Magento 2 source
files and database.

| Argument     | Optional?  | Description |
|--------------|------------|-------------|
| `versions`   | _optional_ | Space-separated list of versions to be cleared or blank to clear all versions. |

### Running CLI commands inside an instance
```shell
m2devbox cli [<container>] [<command>]
```

This command runs CLI commands inside a started container.
If you run `m2devbox cli` without any arguments, a new `bash` session inside the 
`web` container will be started for you. This is most useful if you want to run
Magento CLI commands like `bin/magento cache:clean`.

| Argument     | Optional?  | Description  | Default value |
|--------------|------------|--------------|---------------|
| `container`  | _optional_ | Container name to run the command in. Allowed values: `web` &#124;&#124; `db` &#124;&#124; `elastic` | `web` |
| `command`  | _optional_ | Command to run in the container. | `bash` |
