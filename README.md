#Maxmind Legacy Wrapper #

Обертка для Maxmind Legacy, инкапсулирующая весь стандартный функционал в классы для предотвращения конфликта констант  с установленным модулем Maxmind для Nginx

### Установка через Composer ###

#### Определение зависимостей ####

 [Composer](http://getcomposer.org/).
Для установки добавьте `kubrey/maxgeo` в Ваш `composer.json`. Если этого файла нет, то создайте его в корне сайта

```json
{
    "require": {
        "kubrey/maxgeo": "dev-master"
    },
    "minimum-stability": "dev",
    "repositories":[
        {
            "type":"git",
            "url":"https://bitbucket.org/kubrey/maxgeo"
        },
    ]
}
```

#### Установка Composer ####

Выполнить в корне проекта: 

```
curl -s http://getcomposer.org/installer | php
```

#### Установка зависимостей ####

Выполнить в корне проекта: 

```
php composer.phar install
```

#### Автолоадер ####

Выполнить автозагрузку всех пакетов composer можно подключив скрипт:
```
require 'vendor/autoload.php';
```

### Применение ###

```

require 'vendor/autoload.php';

use MaxmindLegacy\GeoIPCity;
use MaxmindLegacy\GeoIP;

$r = new GeoIP();
try {
    $g = $r->geoip_open('/var/www/GeoLiteCity.dat', GeoIP::GEOIP_STANDARD);
    $c = new GeoIPCity($g);
    var_dump($c->GeoIP_record_by_addr('62.221.80.241'));
    $r->geoip_close();
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
```