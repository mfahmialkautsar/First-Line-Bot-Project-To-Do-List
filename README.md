# Remember Me LINE Bot
As the name said, Remember Me will remember your to-do list and notes so that you never forget that.

This bot can save notes from different source like Personal Chat, Multi Chat, and Group Chat. So you can make Personal To-Do List or To-Do List for team.

## Screenshot


### App Features
* [x] Saving notes
* [x] Database notes for personal, room, and group are seperated
* [x] Greeting message
* [x] Instructions for use
* [x] Auto response
* [x] Fallback message
* [x] Easy to use

### My Official Account
Bot ID: @343bjvaa

### Configuration
This project won't work without some configurations. For me, I uploaded this project to Heroku and used postgresql as database source. So, this configuration must be done in heroku config:
- `APP_KEY`                 : Generate result from web.php
- `CHANNEL_ACCESS_TOKEN`    : From LINE Developers
- `CHANNEL_SECRET`          : From LINE Developers
- `DB_CONNECTION`           : `pgsql`
- `DB_DATABASE`             : From view credentials database
- `DB_HOST`                 : From view credentials database
- `DB_PASSWORD`             : From view credentials database
- `DB_PORT`                 : `5432`
- `DB_USERNAME`             : From view credentials database
- `LOG_CHANNEL`             : `errorlog`

### Licenses
- [Lumen](https://lumen.laravel.com/docs/7.x)
- [PHP](https://www.php.net/docs.php)
- [LINE Messaging API](https://developers.line.biz/en/docs/messaging-api/)
- [Faker](https://github.com/fzaninotto/Faker)
- [PHP Unit](https://packagist.org/packages/phpunit/phpunit)
- [Mockery](https://github.com/mockery/mockery)
 
## Author
* **Fahmi Al Kautsar**