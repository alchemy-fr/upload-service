<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Model\DownloadUrl;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DownloadUrlAction
{
    private $validator;
    private $resourceMetadataFactory;
    /**
     * @var ProducerInterface
     */
    private $downloadProducer;

    public function __construct(
        ValidatorInterface $validator,
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        ProducerInterface $downloadProducer
    ) {
        $this->validator = $validator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->downloadProducer = $downloadProducer;
    }

    public function __invoke(DownloadUrl $data): Response
    {
        $message = \GuzzleHttp\json_encode([
            'url' => $data->getUrl(),
        ]);

        $this->downloadProducer->publish($message);

        return new JsonResponse(true);
    }
}