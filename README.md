# Laravel Crudable Test

This package is very usefull to easily test crudable controllers.

# Installation

You can install package via composer. Add repository to your composer.json

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mindz-team/laravel-crudable-test"
        }
    ],

And run

    composer require mindz-team/laravel-crudable-test

# Usage

This package contains command that creates crudable test to test crudable controller. It creates test class that extends `Mindz\LaravelCrudableTest\Blueprints` blueprint with test methods.

To create class by using this command you need to run

    php artisan make:crudable-test Example

This command creates test class file in  `tests/Feature` directory. Class name is based on controller name provided in command. You can use partial name like `Example` or full like `ExampleController`. After creation you can move class as you please, but remember to adjust namespace.

> Remember that class will not be created if its already exists.

# Test methods

By default command creates methods to test crudable `index`, `show`, `store`, `update`, `destroy` but not every crudable controller utilize all methods. To avoid empty tests you can adjust command to create only methods you need by using command options. Command below will create test class only with desired methods passed to `--only` option as comma separated values

    php artisan make:crudable-test Example --only=index,show

To create methods by exclusion you can use `--except` option. This will creates test methods to all crudable functions but passed to options

    php artisan make:crudable-test Example --except=destroy

# Resources

By default crudable test class tests responses structure against `Illuminate\Http\Resources\Json\JsonResource` class you can indicate your own resource class
for both - collection and single object resource.

    protected function getCollectionResource(): ?string
    {
        return SomeResource::class;
    }

And for single object

    protected function getResource(): ?string
    {
         return SomeSingleObjectResource::class;
    }

> Important think to know is that `getResource` method has also influence to collection resource shape if `getCollectionResource` does not exists

# Default headers

If your application requires additional headers when performing requests you can add globaly headers to all methods by using method `defaultHeaders`

    protected function defaultHeaders(): array
    {
        return [
            'Accept'=>'application/json'
        ];
    }

# User used to perform requests

By default user used to perform requests is created by user factory `User::factory()->make()`. If you like to modify it or add permissions on the fly or something else - you can use method `getUser` which should return class implementing `Authenticable` interface.

    protected function getUser(): Authenticatable
    {
        $user = User::factory()->create();
        $user->addRole('admin');

        return $user;
    }

# Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


# Security

If you discover any security related issues, please email r.szymanski@mindz.it instead of using the issue tracker.

# Credits

Author: Roman Szymański [r.szymanski@mindz.it](mailto:r.szymanski@mindz.it)

# License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
