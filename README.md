## Laravel exposable
[![Latest Stable Version](https://poser.pugx.org/arjanwestdorp/exposable/v/stable?format=flat-square)](https://packagist.org/packages/arjanwestdorp/exposable)
[![License](https://poser.pugx.org/arjanwestdorp/exposable/license?format=flat-square)](https://packagist.org/packages/arjanwestdorp/exposable)
[![Tests](https://github.com/KaneCohen/exposable/actions/workflows/tests.yml/badge.svg)](https://github.com/KaneCohen/exposable/actions/workflows/tests.yml)
[![Quality Score](https://img.shields.io/scrutinizer/g/arjanwestdorp/exposable.svg?style=flat-square)](https://scrutinizer-ci.com/g/arjanwestdorp/exposable)
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/arjanwestdorp/exposable.svg?style=flat-square)](https://scrutinizer-ci.com/g/arjanwestdorp/exposable)
[![StyleCI](https://styleci.io/repos/89977324/shield)](https://styleci.io/repos/89977324)

This is a package to expose your protected models in a secure way. 
You maybe also ran into the problem that you have a file stored in a secure location and you only want to expose it to your users when they are logged in or payed for it.
Laravel exposable will make this much easier for you now.

## Version Compatibility

Exposable **3.0.0** targets PHP **8.4.x** and Laravel **10.50.3+, 11.57.0+,
12.69.3+ and 13.34.0+** within those majors. Carbon 2 is tested on Laravel
10/11; Carbon 3 is tested on Laravel 11–13. Laravel 10/PHP 8.4 is a migration
checkpoint outside Laravel's official PHP support range.

See the [exact tested matrix, fixture provenance and release notes](docs/COMPATIBILITY.md).
Existing valid signed URLs remain compatible when `exposable.key` is unchanged.

Older releases: Exposable 1.0.x targeted Laravel 5.3; 1.1.x targeted Laravel 5.4.

## Installation
The recommended way to install Exposable is through composer:
```bash
composer require arjanwestdorp/exposable
```

Next, you'll have to add the service provider to your `config/app.php`
```php
// config/app.php
'providers' => [
    ...
    ArjanWestdorp\Exposable\ExposableServiceProvider::class,
];
```

Now you'll need to publish the config file:
```bash
php artisan vendor:publish --provider="ArjanWestdorp\Exposable\ExposableServiceProvider" --tag="config"
```

## Usage
Add the `Exposable` trait to the model(s) you want to expose:
```php
namespace App;
    
use ArjanWestdorp\Exposable\Traits\Exposable;
use Illuminate\Database\Eloquent\Model;
    
class File extends Model {
    
    use Exposable;

}
```

Next, you will need to implement the `expose` method on your model:
```php
namespace App;
    
use ArjanWestdorp\Exposable\Traits\Exposable;
use Illuminate\Database\Eloquent\Model;
    
class File extends Model {
    
    use Exposable;
    
    /**
     * Expose the model.
     *
     * @return \Illuminate\Http\Response
     */
    public function expose()
    {
        return response('My secure content');
    }
}
```

Finally you'll need to add the model to the config file `config/exposable.php` and bind it to a key:
```php
'exposables' => [
    'file' => App\File::class,
],
```

Now your model is ready to expose. Simply use the `exposeUrl` method to get the url on which the model will be available.
```php
$file = File::first();
echo $file->exposeUrl();
    
// http://app.app/expose/file/1?expire=1483261800&guard=member&signature=716817ecaed63fa8b1b887b64ab9505d90cf065dc0677d8b011e3a8b014c43e0
```

## Configuration
Configuration is mainly done through the config file. Although there is the option to deviate on model level.

### Config file
Below an explanation of all options in the [config file](config/exposable.php).

#### key
The key which is used to sign the expose urls. By default it uses the Laravel key of you application.
```
Default: config('app.key')
```

#### url-prefix
The prefix of the url on which the models will be exposed.
```
Default: '/expose'
```
When exposing the complete url would look like: 
```
http://app.app/expose/file/1?expire=1483261800&guard=member&signature=716817ecaed63fa8b1b887b64ab9505d90cf065dc0677d8b011e3a8b014c43e0
```

#### middleware
Here you can define middleware of your application you want to include for the expose url.
```
Default: 'web'
```

#### lifetime
The time after which the url expires. When an integer is given the time is in minutes.
 Any valid date modification can be used like `2 hours`, `1 day`. See [http://php.net/manual/en/datetime.formats.relative.php](http://php.net/manual/en/datetime.formats.relative.php) for all allowed formats.
```
Default: 10
```

#### exposables
Array containing all the models you want to expose. The key is used in the url to retrieve the corresponding model.
```
Default: []
```

#### guards
Array of guards which can protect the exposables. These are NOT the same as the Laravel guards. A custom guard can be very usefull when you want to expose a model only to authenticated users that have payed for the content for example. See [Custom guards](#custom-guards) for an example. 
```
Default: ['auth' => \ArjanWestdorp\Exposable\Guards\AuthGuard::class]
```

#### default-guard
The default guard that is used to check if the model can be exposed. You can override this settings on the exposable model if needed.
Set to null if you don't want a guard check. That only works when the `require-guard` is set to false.
```
Default: 'auth'
```

#### require-guard
Define if a guard is always required when exposing a model. Settings this to false will give you the option to use no guard by setting the `default-guard` option to null or `$exposableGuard` on a model.
```
Default: true
```

### Model configuration
If a guard or lifetime property is absent, the configured default is used.
An explicitly null property stays null. `setExposableGuard()` and
`setExposableLifetime()` override the current instance without creating dynamic
properties or database attributes. Runtime overrides are not persisted to the database. Clones retain those
settings and can override them independently.

On a model you can override the default lifetime and the guard which are used to expose:
```php
namespace App;
    
use ArjanWestdorp\Exposable\Traits\Exposable;
use Illuminate\Database\Eloquent\Model;
    
class File extends Model {
    
    use Exposable;
    
    /**
     * The time the expose url will be valid.
     *
     * @var string
     */
    protected $exposableLifetime = '2 hours';
    
    /**
     * The guard to use when exposing this model.
     *
     * @var string
     */
    protected $exposableGuard = 'member';
    
    /**
     * Expose the model.
     *
     * @return \Illuminate\Http\Response
     */
    public function expose()
    {
        return response('My secure content');
    }
}
```

## Custom guards
You can define your own custom guards which will be checked when accessing the expose url.
These guards will need to implement the `ArjanWestdorp\Exposable\Guards\Guard` interface.
An example of using a custom guard can be when checking if a user is not only authenticated, but also a member:

```php
namespace App\Guards;
    
use ArjanWestdorp\Exposable\Guards\Guard;
 
class MemberGuard implements Guard{
    
    /**
     * Check if the user is authenticated and if he is a member.
     * 
     * @return bool
     */
    public function authenticate(){
        if(!auth()->check()){
            return false;
        }
        
        return auth()->user()->isMember();
    }
}
```

Define this guard in the `config/exposable.php` config file:
```php
'guards' => [
    ...
    'member' => \App\Guards\MemberGuard::class,
],
```

Now you're good to go and set either the `default-guard => 'member'` in your config file or set the `protected $exposableGuard = 'member'` on your model.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Security

If you discover any security issues, please email arjanwestdorp@gmail.com instead of creating an issue.

## Credits

- [Arjan Westdorp](https://github.com/arjanwestdorp)
- [Trait Development](http://www.trait-development.nl)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
