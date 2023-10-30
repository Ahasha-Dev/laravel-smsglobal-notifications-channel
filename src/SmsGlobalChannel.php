<?php

namespace SalamWaddah\SmsGlobal;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGlobalChannel
{
    private Credentials $credentials;
    protected Dispatcher $events;

    private const NAMESPACE = 'smsglobal';

    public function __construct(Credentials $credentials, Dispatcher $events)
    {
        $this->credentials = $credentials;
        $this->events = $events;
    }

    /**
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function send($notifiable, Notification $notification): void
    {
        try {
            $to = $this->getTo($notifiable, $notification);

            /* @var SmsGlobalMessage $message */
            $message = $notification->toSmsGlobal($notifiable);

            if (! $message instanceof SmsGlobalMessage) {
                throw Exceptions\CouldNotSendNotification::invalidMessageObject($message);
            }

            $smsParameters = $this->toArray($message, $to);

            if (Config::get('services.sms_global.debug')) {
                Log::info(
                    sprintf(
                        'SMS GLOBAL: Sending sms to %s: %s',
                        $to,
                        $message->getContent()
                    ),
                    $smsParameters
                );

                Log::debug('SMS GLOBAL: Debug mode is ON.');

                return;
            }

            $response = Http::withHeaders([
                'Authorization' => $this->credentials->getAuthorizationHeader(),
                'Content-Type' => 'application/json',
            ])->post($this->credentials->getUrl(), $smsParameters);

            $response->throw();
        } catch (Exception $exception) {
            $event = new NotificationFailed(
                $notifiable,
                $notification,
                self::NAMESPACE,
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            $this->events->dispatch($event);

            throw $exception;
        }
    }

    public function toArray(SmsGlobalMessage $message, $to): array
    {
        return [
            'destination' => $to,
            'message' => $message->getContent(),
            'origin' => $this->getOrigin(),
        ];
    }

    /**
     * Get the address to send a notification to.
     *
     * @param mixed $notifiable
     * @param Notification|null $notification
     *
     * @return mixed
     * @throws Exceptions\CouldNotSendNotification
     */
    protected function getTo($notifiable, $notification = null)
    {
        if ($notifiable->routeNotificationFor(self::class, $notification)) {
            return $notifiable->routeNotificationFor(self::class, $notification);
        }

        if ($notifiable->routeNotificationFor(self::NAMESPACE, $notification)) {
            return $notifiable->routeNotificationFor(self::NAMESPACE, $notification);
        }

        if (isset($notifiable->phone_number)) {
            return $notifiable->phone_number;
        }

        throw Exceptions\CouldNotSendNotification::invalidReceiver();
    }

    public function getOrigin(): string
    {
        return Config::get('services.sms_global.origin');
    }
}
