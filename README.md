# MuckWebInterface
This project contains the framework to run the supporting webpage for the muck.

## Deployment
Make sure Composer and NPM are installed  
Clone project from git or Download files to a folder    
Copy .env.example to .env and fill out the settings in it  

The following resources need to be downloaded separately:
* /public/sitelogo.png
* /public/favicon32.png
* /storage/app/avatar/

### Production Deployment
```
php composer.phar install --optimize-autoloader --no-dev  
npm install
php artisan key:generate
php artisan config:cache
php artisan route:cache
npm run build
php artisan migrate
```
### Development Deployment
Project uses Laravel Sail so see the documentation for that for more information.
```  
php composer.phar install  
npm install
sail up -d
sail artisan key:generate  
sail artisan migrate --seed
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear
```
Then use the following to watch during development: `sail npm run dev`

(Optional) The websocket emulator can be run with: `sail artisan websocket:serve` 

## Updating

### Production Updating
```
php artisan down
git pull origin master
php composer.phar install --optimize-autoloader --no-dev
php artisan migrate
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
// php artisan queue:restart
npm install
npm run build
php artisan up
```

## Maintenance cheat sheet
* Entire web server can be disabled by running `php artisan down` from the root dir. Opposite is `php artisan up`.
* Muck equivalent is `mwi down` and `mwi up` which will refuse connections between it and the web server.
* Environment variables (Such as credentials) stored in `/.env`
* Logs are located in `/storage/logs/`
* Terms-of-service located in `/public/terms-of-service.txt`

## Roles
Main recognized roles are:
* Staff - can be inherited from a character with W1 and above
* Admin - can be inherited from a character with W3 and above
* Siteadmin - grants EVERY role

## Active character
* Works on top of account validation, which authenticates the account beforehand.
* A request is made to the muck to validate a character exists and belongs to the present account. It also checks the character hasn't been locked in some way.
* Pages served have a 'character-dbref' meta property to say which character they're for.
* Axios Requests have an X-Character-Dbref header to 'sign' them to the correct character.
* A 'character-dbref' is also set on the cookie to allow remembering a previous character between sessions. It's vulnerable to cross-tab pollution so ONLY for starting the session.    
  

  
