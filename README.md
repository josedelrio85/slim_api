# Bysidecar Webservice API

  The goal of this API is to handle the requests received from different kind of environments and for the different campaigns implemented.

  Uses Slim 3 Framework and PHP 7.1 + MySQL.

## To install, run

  ```php
  composer install

  ```

## Launch web server

```php
php -S localhost:8888 index.php
```

Use -c flag with the path to a php.ini file to use its configuration

## Endpoints

 RCable incoming C2C

- <https://ws.bysidecar.es/api/public/index.php/rcable/incomingC2C>

 ```json
 {
  "phone":"665932355",
  "utm_source": "",
  "sub_source": "",
  "gclid": "",
 }
 ```

 Sanitas incoming C2C

- <https://ws.bysidecar.es/api/public/index.php/sanitas/incomingC2C>

 ```json
 {
   "phone": "665932355",
   "utm_source": "default",
   "sub_source": null,
   "producto": "Bysidecar test",
   "name": "Bysidecar test",
 }
 ```

 EVO status

- <https://ws.bysidecar.es/api/public/index.php/clients/status/{provider}>

 ```json
 {
   "client_id": "XXX",
 }
 ```
