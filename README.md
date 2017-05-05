# deter-b-sync-data-client
Using the PHP with cURL over DETER-B service API to create a client that synchronize data.

## Dependencies

- This project is organized using Composer and the used version is [1.3.1](https://getcomposer.org/download/1.3.1/composer.phar)
- Other technique used is the [PSR-4 autoload spec](http://www.php-fig.org/psr/psr-4/).
	- If a new path do registered on composer.json in autoload property, use this command [#php composer.phar dumpautoload -o] to update the composer autoload file.
	
## Installation (tested in Linux - Ubuntu 14.04)

The expected environment to deployment is composed for:
- PHP 5

  -Install curl module on php.
  
  ```
  apt-get install php5-curl
  ```
  
  -Install the php composer on root directory of the project.
  
  ```
  wget https://getcomposer.org/download/1.3.2/composer.phar
  ```

### Installing dependecies from composer.json
 - To install the defined dependencies for project, just run the install command.
 
  ```
  php composer.phar install
  ```