# Remember Me LINE Bot
Remember Me will remember your To-Do List and Notes so that you never forget them.

This bot can save notes from different source like Personal Chat, Multi Chat, and Group Chat. So you can make your Personal To-Do List or To-Do List for team.

## Screenshot
<img src="./resources/personal%20chat.jpg" width="512" title="Personal Chat">

### App Features
* [x] Saving notes
* [x] Database notes for personal, room, and group are seperated
* [x] Greeting message
* [x] Instructions for use
* [x] Auto response
* [x] Fallback message
* [x] Easy to use

### Official Account Sample
Bot ID: @343bjvaa

### Configuration
This project won't work without some configurations. For me, I uploaded this project to Heroku and used postgresql as database source. So, this configuration must be done in heroku config:
- `CHANNEL_ACCESS_TOKEN`: From LINE Developers
- `CHANNEL_SECRET`: From LINE Developers

If it DOESN'T WORK, add these all too:
- `DB_CONNECTION`: `pgsql`
- `DB_DATABASE`: From view credentials database
- `DB_HOST`: From view credentials database
- `DB_PASSWORD` : From view credentials database
- `DB_PORT`: `5432`
- `DB_USERNAME`: From view credentials database

And set Webhook URL as: `https://your-domain/public/webhook`

### Licenses
- [Lumen](https://lumen.laravel.com/docs/7.x)
- [PHP](https://www.php.net/docs.php)
- [LINE Messaging API](https://developers.line.biz/en/docs/messaging-api/)
 
## Author
* **Fahmi Al Kautsar**
