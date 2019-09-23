<?php

declare(strict_types=1);

namespace App\Mail;

use App\Consumer\Handler\NotifyUserHandler;
use App\Consumer\Handler\SendEmailHandler;
use Arthem\Bundle\RabbitBundle\Consumer\Event\EventMessage;
use Arthem\Bundle\RabbitBundle\Producer\EventProducer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MailerRabbitProxy
{
    /**
     * @var EventProducer
     */
    private $eventProducer;
    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(EventProducer $eventProducer, Mailer $mailer)
    {
        $this->eventProducer = $eventProducer;
        $this->mailer = $mailer;
    }

    public function sendEmail(Request $request)
    {
        $email = $request->request->get('email');
        if (!$email) {
            throw new BadRequestHttpException('Missing email');
        }
        $template = $request->request->get('template');
        if (!$template) {
            throw new BadRequestHttpException('Missing template');
        }
        $parameters = $request->request->get('parameters', []);
        if (!is_array($parameters)) {
            throw new BadRequestHttpException('parameters must be an array');
        }

        $this->mailer->validateParameters($template, $parameters);

        $this->eventProducer->publish(new EventMessage(SendEmailHandler::EVENT, [
            'email' => $email,
            'template' => $template,
            'parameters' => $parameters,
        ]));
    }

    public function notifyUser(Request $request)
    {
        $userId = $request->request->get('user_id');
        if (!$userId) {
            throw new BadRequestHttpException('Missing user_id');
        }
        $contactInfo = $request->request->get('contact_info', []);
        if (!is_array($contactInfo)) {
            throw new BadRequestHttpException('contact_info must be an array');
        }

        $template = $request->request->get('template');
        if (!$template) {
            throw new BadRequestHttpException('Missing template');
        }

        $parameters = $request->request->get('parameters', []);
        if (!is_array($parameters)) {
            throw new BadRequestHttpException('parameters must be an array');
        }

        $this->mailer->validateParameters($template, $parameters);

        $this->eventProducer->publish(new EventMessage(NotifyUserHandler::EVENT, [
            'user_id' => $userId,
            'template' => $template,
            'parameters' => $parameters,
            'contact_info' => $contactInfo,
        ]));
    }
}