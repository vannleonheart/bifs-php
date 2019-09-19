## Logger

```php
<?php

require_once('./Services/Logger.php');

$config = array(
    'project' => 'PROJECT_ID',
    'service' => 'SERVICE_ID',
    'auth' => array(
        'key' => 'AUTH_KEY',
        'secret' => 'AUTH_SECRET'
    )
);

try {
    $logger = new Bifs\Services\Logger($config);
    $logger->capture('EVENT_NAME', 'EVENT_DATA', 'EVENT_CATEGORY')(array(
        'VARS_1_KEY' => 'VARS_1_VALUE',
        'VARS_2_KEY' => 'VARS_2_VALUE'
    ), 'CHANNEL_ID');    
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
```
