# Setup Instructions for Windows Subsystem for Linux (WSL)

You can set up a local development environment on Windows with [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/).

## Pre-requisites

You should have the following already set up on your Windows computer:

-   **Docker Desktop for Windows:** Follow the setup instructions from the official [Docker documentation](https://docs.docker.com/docker-for-windows/install/)
-   **WSL 2:** Follow the setup instructions from the official [WSL documentation](https://docs.microsoft.com/en-us/windows/wsl/install)
-   **Set default Linux distribution:** To avoid having to specify the Linux distribution every time you use the `wsl` command, set it as the defaul distro by following the instructions from the official [WSL documentation](https://docs.microsoft.com/en-us/windows/wsl/basic-commands#set-default-linux-distribution)

## Setup Steps



1. Update and upgrade packages.

    ```bash
    sudo apt update -y && sudo apt upgrade -y
    ```

1. Install PHP.

    ```bash
    sudo apt install -y php
    ```

    To verify that PHP was installed correctly, run:

    ```bash
    php -v
    ```

    You should see the result to be something like:

    ```
    PHP 8.1.2-1ubuntu2.11 (cli) (built: Feb 22 2023 22:56:18) (NTS)
    Copyright (c) The PHP Group
    Zend Engine v4.1.2, Copyright (c) Zend Technologies
        with Zend OPcache v8.1.2-1ubuntu2.11, Copyright (c), by Zend Technologies
    ```

1. Install Composer.

    ```bash
    cd ~
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    HASH=`curl -sS https://composer.github.io/installer.sig`
    echo $HASH # should print the correct hash
    ```



1. 
-   PHP
-   Composer
-   `php-xml`
-   `php-mbstring`

```bash
sudo apt install php-cli unzip -y

cd ~

curl -sS https://getcomposer.org/installer -o composer-setup.php

HASH=`curl -sS https://composer.github.io/installer.sig`

echo $HASH

php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

composer --version --no-interaction # Verify that Composer installation was successful

sudo apt install php-xml -y

sudo apt install php-mbstring -y
```

For Puppeteer to run in headless mode you'll need to install additional packages:

```bash
sudo apt install -y ca-certificates fonts-liberation gconf-service libappindicator1 libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release wget xdg-utils
```

Add your username to the `docker` group to avoid having to type `sudo` when you run Docker commands.

```bash
sudo usermod -aG docker ${YOUR_USERNAME}

su - ${YOUR_USERNAME}
```

At this point, you're now ready to proceed with the steps in [Prep work for running tests](./README.md#prep-work-for-running-tests).
