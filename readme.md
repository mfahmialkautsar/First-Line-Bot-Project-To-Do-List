This is a First Line Bot I've ever created. It comes from php language and lumen from laravel that also I never learnt before.

You can save your To-Do List with this bot. This bot can save note from different source like Personal Chat, Multi Chat, and Group Chat. So you can make Personal To-Do List or To-Do List for team.

And of course this project won't work without some configurations. I uploaded this to Heroku and using postgresql as database source. So this configuration must be done in heroku config:

APP_KEY : Generate result from web.php

CHANNEL_ACCESS_TOKEN : From LINE Developers

CHANNEL_SECRET : From LINE Developers

DB_CONNECTION : pgsql

DB_DATABASE : From view credentials database

DB_HOST : From view credentials database

DB_PASSWORD : From view credentials database

DB_PORT : 5432

DB_USERNAME : From view credentials database

LOG_CHANNEL : errorlog

Big thanks to Dicoding Indonesia and Line Indonesia that give me this lesson. I will never taste Line Bot if it's not from you.