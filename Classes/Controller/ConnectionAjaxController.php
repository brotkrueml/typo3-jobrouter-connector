<?php

declare(strict_types=1);

/*
 * This file is part of the "jobrouter_connector" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Brotkrueml\JobRouterConnector\Controller;

use Brotkrueml\JobRouterConnector\Domain\Model\Connection;
use Brotkrueml\JobRouterConnector\Domain\Repository\ConnectionRepository;
use Brotkrueml\JobRouterConnector\RestClient\RestClientFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 @internal
 */
final class ConnectionAjaxController
{
    /** @var ObjectManager */
    private $objectManager;

    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
    }

    public function checkAction(ServerRequestInterface $request): ResponseInterface
    {
        $connectionId = (int)$request->getParsedBody()['connectionId'];

        $result = ['check' => 'ok'];
        try {
            $connectionRepository = $this->objectManager->get(ConnectionRepository::class);
            /** @var Connection $connection */
            $connection = $connectionRepository->findByIdentifierWithHidden($connectionId);

            if ($connection) {
                (new RestClientFactory())->create($connection, 10);
            } else {
                $result = ['error' => sprintf('Connection with id "%d" not found!', $connectionId)];
            }
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage()];
        }

        return new JsonResponse($result);
    }
}
