<?php

declare(strict_types=1);

namespace Alchemy\NotifyBundle\Notify;

use GuzzleHttp\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Notifier implements NotifierInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function sendEmail(string $email, string $template, string $locale, array $parameters = []): void
    {
        $this->logger->debug(sprintf('Send email to"%s" with template "%s"', $email, $template));

        $this->client->request('GET', '/send-email', [
            'json' => [
                'email' => $email,
                'template' => $template,
                'parameters' => $parameters,
                'locale' => $locale,
            ],
        ]);
    }

    public function notifyUser(
        string $userId,
        string $template,
        array $parameters = [],
        array $contactInfo = null
    ): void
    {
        $data = [
            'user_id' => $userId,
            'template' => $template,
            'parameters' => $parameters,
        ];
        if (null !== $contactInfo) {
            $data['contact_info'] = $contactInfo;
        }

        $this->logger->debug(sprintf('Notify user "%s" with template "%s"', $userId, $template));

        $this->client->request('GET', '/notify-user', [
            'json' => $data,
        ]);
    }

    public function registerUser(string $userId, array $contactInfo): void
    {
        $data = [
            'user_id' => $userId,
            'contact_info' => $contactInfo,
        ];

        $this->logger->debug(sprintf('Register user "%s" to notifier', $userId));

        $this->client->request('GET', '/register-user', [
            'json' => $data,
        ]);
    }
}
