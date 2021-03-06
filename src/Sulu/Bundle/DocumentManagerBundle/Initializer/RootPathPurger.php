<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Doctrine\Common\Persistence\ConnectionRegistry;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\PathSegmentRegistry;

/**
 * Purges the root path only.
 */
class RootPathPurger implements PurgerInterface
{
    /**
     * @var string
     */
    private $rootRole;

    /**
     * @var SessionInterface[]
     */
    private $connections;

    /**
     * @var PathSegmentRegistry
     */
    private $pathSegments;

    public function __construct(ConnectionRegistry $connections, PathSegmentRegistry $pathSegments, $rootRole = 'root')
    {
        $this->rootRole = $rootRole;
        $this->connections = $connections;
        $this->pathSegments = $pathSegments;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $sessions = $this->connections->getConnections();
        $rootPath = '/' . $this->pathSegments->getPathSegment($this->rootRole);

        foreach ($sessions as $session) {
            try {
                $session->getRootNode();
            } catch (RepositoryException $e) {
                // TODO: We should catch the more explicit
                // WorkspaceNotFoundException but Jackalope doctrine-dbal does
                // not throw this: https://github.com/jackalope/jackalope-doctrine-dbal/issues/322
                continue;
            }

            if ($session->nodeExists($rootPath)) {
                $session->getNode($rootPath)->remove();
                $session->save();
            }
        }
    }
}
