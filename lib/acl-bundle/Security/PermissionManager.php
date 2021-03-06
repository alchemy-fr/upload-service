<?php

declare(strict_types=1);

namespace Alchemy\AclBundle\Security;

use Alchemy\AclBundle\AclObjectInterface;
use Alchemy\AclBundle\Entity\AccessControlEntry;
use Alchemy\AclBundle\Mapping\ObjectMapping;
use Alchemy\AclBundle\Repository\PermissionRepositoryInterface;
use Alchemy\AclBundle\UserInterface;
use Alchemy\RemoteAuthBundle\Model\RemoteUser;

class PermissionManager
{
    private ObjectMapping $objectMapper;
    private PermissionRepositoryInterface $repository;

    public function __construct(ObjectMapping $objectMapper, PermissionRepositoryInterface $repository)
    {
        $this->objectMapper = $objectMapper;
        $this->repository = $repository;
    }

    /**
     * @param UserInterface|RemoteUser $user
     */
    public function isGranted($user, AclObjectInterface $object, int $permission): bool
    {
        $objectKey = $this->objectMapper->getObjectKey($object);

        /** @var AccessControlEntry[] $aces */
        $aces = $this->repository->getAces(
            $user->getId(),
            $user->getGroupIds(),
            $objectKey,
            $object->getId()
        );

        foreach ($aces as $ace) {
            if (null !== $ace && ($ace->getMask() & $permission) === $permission) {
                return true;
            }
        }

        return false;
    }
}
