<?php

declare(strict_types=1);

namespace App\Elasticsearch\Listener;

use Alchemy\AclBundle\Security\PermissionInterface;
use Alchemy\AclBundle\Security\PermissionManager;
use App\Entity\Core\Asset;
use App\Entity\Core\Collection;
use FOS\ElasticaBundle\Event\PostTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CollectionPostTransformListener implements EventSubscriberInterface
{
    private PermissionManager $permissionManager;

    public function __construct(PermissionManager $permissionManager)
    {
        $this->permissionManager = $permissionManager;
    }

    public function hydrateDocument(PostTransformEvent $event): void
    {
        /** @var Collection $collection */
        if (!($collection = $event->getObject()) instanceof Collection) {
            return;
        }

        $document = $event->getDocument();

        $isPublic = $collection->isPublicOrHasPublicParent()
            || $collection->isPublicOrHasPublicChild();

        [$users, $groups] = $this->discoverChildren($collection);

        $parent = $collection->getParent();
        while (null !== $parent) {
            $users = array_merge($users, $this->permissionManager->getAllowedUsers($parent, PermissionInterface::VIEW));
            $groups = array_merge($groups, $this->permissionManager->getAllowedGroups($parent, PermissionInterface::VIEW));
            $parent = $parent->getParent();
        }

        $document->set('public', $isPublic);
        $document->set('users', array_values(array_unique($users)));
        $document->set('groups', array_values(array_unique($groups)));
    }

    private function discoverChildren(Collection $collection): array
    {
        $users = [];
        if (null !== $collection->getOwnerId()) {
            $users[] = $collection->getOwnerId();
        }

        $users = array_merge($users, $this->permissionManager->getAllowedUsers($collection, PermissionInterface::VIEW));
        $groups = $this->permissionManager->getAllowedGroups($collection, PermissionInterface::VIEW);

        foreach ($collection->getChildren() as $child) {
            [$u, $g] = $this->discoverChildren($child);
            $users = array_merge($users, $u);
            $groups = array_merge($groups, $g);
        }

        return [array_values(array_unique($users)), array_values(array_unique($groups))];
    }

    public static function getSubscribedEvents()
    {
        return [
            PostTransformEvent::class => 'hydrateDocument',
        ];
    }

}
