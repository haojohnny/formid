<h1 align="center"> formID </h1>

<p align="center">基于Redis的微信小程序form-id的收集管理工具</p>


## 安装

```shell
$ composer require miniprogram/formid -vvv
```

## form-id的生命周期
   
       
                         save                                                                            get
    [form-value3, 7day] ------> { [form-value3, 7day], [form-value2, 5day], [form-value1, 1day], ... } ------> form-value1
                                                                                                  |
                                                                                                  | auto remove
                                                                                                  v                                                             
                                                                                            [form-value0, -1day]
## 配置                                                                         

在Redis.php文件中配置Redis连接
```php
protected $options = [
    'host'       => 'redis',
    'port'       => 6379,
    'password'   => '',
    'select'     => 0,
    'timeout'    => 0,
    'expire'     => 0,
    'persistent' => false,
];
```
在FormId.php文件中配置
```php
protected $config = [
    'prefix' => 'form_id_', // form-id缓存前缀
    'count' => 50,          // form-id最大保存数量
    'expire' => '7 days'    // form-id有效期（必须是strtotime函数能识别的语义字符串，有效期必须小于等于7天）
];
```

## 使用方法
```php
require './vendor/autoload.php';

use Miniprogram\Formid\FormId;

// 方法一：数据存储默认使用Redis.php的连接实例
$form = new FormId();

// 方法二：手动注入redis实例
$redis = new \Redis;
$redis->connect('127.0.0.1', 6379, 0);
$form = new FormId($redis);

// 在实例化时传入form-id配置
$options = [
    'prefix' => 'form_id_',
    'count'  => 50,
    'expire' => '7 days'
];

$redis = new \Redis;
$redis->connect('127.0.0.1', 6379, 0);

$form = new FormId($redis, $options);
```

保存form-id

```php
$form->save('user_id:1001', 'form-value');
```

获取form-id，不存在时返回null

```php
echo $form->get('user_id:1001'); // form-value
return $form->get('user_id:1002'); // null
```

TODO

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/miniprogram/formID/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/miniprogram/formID/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT