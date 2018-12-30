# After cloning this project make the following:

## Install the googleapi sdk in your project
``composer install``
## Change the API call version at this file
``` /path/to/project/vendor/google/apiclient-services/src/Google/Service/Drive.php, _constructor```

from
```
$this->servicePath = 'drive/v3/';
$this->version = 'v3';
```
to 
```
$this->servicePath = 'drive/v2/';
$this->version = 'v2';
```
Then you need to change the default nginx block to 
```
server {
        charset utf-8;
        client_max_body_size 128M;

        listen 80 default_server;
		listen [::]:80 default_server;

        root        /path/to/project/frontend/web/;
        index       index.php;

        access_log  /path/to/project/frontend-access.log;
        error_log   /path/to/project/frontend-error.log;

        location / {
            # Redirect everything that isn't a real file to index.php
            try_files $uri $uri/ /index.php$is_args$args;
        }

        # uncomment to avoid processing of calls to non-existing static files by Yii
        #location ~ \.(js|css|png|jpg|gif|swf|ico|pdf|mov|fla|zip|rar)$ {
        #    try_files $uri =404;
        #}
        #error_page 404 /404.html;

        # deny accessing php files for the /assets directory
        location ~ ^/assets/.*\.php$ {
            deny all;
        }

        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            #fastcgi_pass 127.0.0.1:9000;
            fastcgi_pass unix:/var/run/php/php7.1-fpm.sock;
            try_files $uri =404;
        }

        location ~* /\. {
            deny all;
        }
    }
```
## Reload nginx, open your browser and got to 
``http://localhost/site/driveurl ``
