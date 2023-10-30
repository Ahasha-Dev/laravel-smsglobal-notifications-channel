Use this package to send SMS with [SmsGlobal](https://www.smsglobal.com/) in `Laravel 10`.

## Installation

```
composer require ahasha-dev/laravel-smsglobal-notifications-channel
```

## Configure

In your `services.php` config file add the following configs.

```
    // ... 
    
    'sms_global' => [
        'debug' => env('SMS_GLOBAL_DEBUG', true),
        'api_key' => env('SMS_GLOBAL_API_KEY'),
        'api_secret' => env('SMS_GLOBAL_API_SECRET'),
        'origin' => 'YourCompanyName',
    ],
```

## Debug Mode

Debug mode is turn on by default, which means SMS will not be actually sent, instead only a log record will be added
to `/storage/logs/laravel.log`

In your `services.php` change the value of `sms_global.debug` to `false`

## Usage

### Notification class

Using Laravel [notification class](https://laravel.com/docs/8.x/notifications) add `SmsGlobalChannel::class` to `via()`
method like so:

```php
use Illuminate\Notifications\Notification;
use SalamWaddah\SmsGlobal\SmsGlobalChannel;
use SalamWaddah\SmsGlobal\SmsGlobalMessage;

class OrderPaid extends Notification
{

    public function via($notifiable): array
    {
        return [
            SmsGlobalChannel::class,
        ];
    }

    public function toSmsGlobal(): SmsGlobalMessage
    {
        $message = 'Order paid, Thank you for your business!';

        $smsGlobal = new SmsGlobalMessage();

        return $smsGlobal->content($message);
    }
}
```

### On demand notification

You can utilize Laravel on-demand notification facade to send SMS directly to a phone number without having to store a user in your application.

```php
Notification::send(
    '+971555555555',
    new OrderPaid($order)
);
```

The notifiable argument in `toSmsGlobal` of your notification class should expect the same data type you passed to
the `Notification` facade.

```php
public function toSmsGlobal(): SmsGlobalMessage
{
    $message = 'Order paid, Thank you for your business!';

    $smsGlobal = new SmsGlobalMessage();

    return $smsGlobal->content($message);
}
```
