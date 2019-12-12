<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetPublicationAction extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(string $id): Publication
    {
        $params = Uuid::isValid($id) ? ['id' => $id] : ['slug' => $id];
        $publication = $this->em
            ->getRepository(Publication::class)
            ->findOneBy($params);

        if (
            !$publication instanceof Publication
            || !$publication->isEnabled() && !$this->isGranted('ROLE_ADMIN')
        ) {
            throw new NotFoundHttpException();
        }

        return $publication;
    }
}