# Refactor

PHP version: 8.0.3

> Quick note: I hate **Repository Pattern**. its making the code hard to read and its complicated. I've once tried implemented with my team to build e-commerce and its been probably the biggest mistake I've made for the team. I prefer to use **Service Pattern** or if you know [Laravel Action](https://github.com/lorisleiva/laravel-actions) I'am a fan of this package. But, I will try to follow the **Repository Pattern** as much as possible.

### Method: `__construct` (`BookingController.php`)

- Use **Constructor Property Promotion** by removing property declaration, property assignment and less code.

#### Reference

- [Constructor Property Promotion](https://wiki.php.net/rfc/constructor_promotion)

### Method: `index()` (`BookingController.php`)

- Rename the variable from `$user_id` to `$userId` this follow the **PSR-12 standard** or follow **Laravel Best Practice** guide. I always use camelCase for variable names.
- Moved the `$userId` assignment outside the conditional check. This is to avoid the same code being repeated twice. and much easier to read.
- Store the user type in variable `$authenticatedUserType` to avoid redundant calls to `$request->__authenticatedUser->user_type`. and notice I added a **null safe operator** `?` before the `->` to avoid a possible error if the `__authenticatedUser` is `null`. since I am not sure if it can only be accessed if the user is authenticated.
- Use `config` instead of `env()`. Why? So Laravel has this command `php artisan config:cache` which will cache all the config files into a single file. Once the configuration has been cached, the `.env` file will not be loaded and all calls to the env function for `.env` variables will return `null`.
- And lastly, I added `else` statement to the conditional check. and since both `getUsersJobs` and `getAll` return an array. I add default `$response = []` in case none of the conditions are met.

#### Reference

- [PSR-12 standard](https://www.php-fig.org/psr/psr-12/)
- [Laravel Best Practice](https://github.com/alexeymezenin/laravel-best-practices)
- [Nullsafe operator](https://wiki.php.net/rfc/nullsafe_operator)
- [Configuration Caching](https://laravel.com/docs/10.x/configuration#configuration-caching)

### Method: `show()` (`BookingController.php`)

- I prefer using `findOrFail` instead of `find` because it throws an error, while `find` doesn't.

There isn't much change made here, but you can utilize using **Route Model Binding** (I am not a fan of this approach), and it looks like this (not using the Repository Pattern):

```php
public function show(Job $job)
{
    $job->load('user');
    return response($job);
}
```

### Method: `store()` (`BookingController.php`)

- I added a validation to the request. instead of direct using `$request->all()`. so that this way we sure only the data we need is being passed to the controller.

## Changed

1. `array()` to `[]`
2. `env()` to `config()`
3. `find()` to `findOrFail()`
4. `env('APP_ENV')` to `App::environment()`
5. `isset()` to `??`
6. Removed single-statement `if` braces. (I prefer to use braces. its much easier to read)
7. Use **Database Transactions** for multiple database operations.

## Thoughts

The code is ok. but there is a lot of room for improvement.

- There is some code duplication. extract common functionality into a method.
- Complex conditional check.
- Hard to read. The `BookingController` look good and easy to read. but the `BookingRepository` is hard to read.
- Hardcoded values. I like to define constant or enum.
- Error handling. There's no error handling in case of database queries or other potential issues. also this can reduce the number of `if` statements.
- Multiple database operations without using **Database Transactions**.
- Use request validation. there is no need to use `if` statements to validate the request data. also instead of using `$request->all()` try to get only data thats been validated.
- Use new PHP features. like **Constructor Property Promotion** and **Nullsafe operator** etc. (keep update about new PHP features)
- Too many unused variables.
