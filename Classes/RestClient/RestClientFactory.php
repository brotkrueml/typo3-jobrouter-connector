<?php

declare(strict_types=1);

/*
 * This file is part of the "jobrouter_connector" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Brotkrueml\JobRouterConnector\RestClient;

use Brotkrueml\JobRouterClient\Client\ClientInterface;
use Brotkrueml\JobRouterClient\Client\RestClient;
use Brotkrueml\JobRouterClient\Configuration\ClientConfiguration;
use Brotkrueml\JobRouterClient\Exception\ExceptionInterface;
use Brotkrueml\JobRouterConnector\Domain\Model\Connection;
use Brotkrueml\JobRouterConnector\Domain\Repository\ConnectionRepository;
use Brotkrueml\JobRouterConnector\Service\Crypt;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

final class RestClientFactory
{
    /**
     * @var string
     */
    private static $version;

    /**
     * Creates the Rest client for the given connection
     *
     * @param Connection $connection The connection model
     * @param int|null $lifetime Optional lifetime argument
     * @param string|null $userAgentAddition Addition to the user agent
     * @return ClientInterface
     * @throws ExceptionInterface
     */
    public function create(
        Connection $connection,
        ?int $lifetime = null,
        ?string $userAgentAddition = null
    ): ClientInterface {
        $decryptedPassword = (new Crypt())->decrypt($connection->getPassword());

        $configuration = new ClientConfiguration(
            $connection->getBaseUrl(),
            $connection->getUsername(),
            $decryptedPassword
        );

        $configuration = $configuration->withUserAgentAddition($userAgentAddition ?? $this->getUserAgentAddition());

        if ($lifetime) {
            $configuration = $configuration->withLifetime($lifetime);
        }

        $client = new RestClient($configuration);

        $this->updateJobRouterVersion($client, $connection);

        return $client;
    }

    /**
     * @return string
     * @norector Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector
     */
    private function getUserAgentAddition(): string
    {
        if (empty(static::$version)) {
            include ExtensionManagementUtility::extPath('jobrouter_connector') . '/ext_emconf.php';
            static::$version = \array_pop($EM_CONF)['version'];
        }

        return \sprintf(
            'TYPO3-JobRouter-Connector/%s (https://typo3-jobrouter.rtfd.io/projects/connector/)',
            static::$version
        );
    }

    private function updateJobRouterVersion(ClientInterface $client, Connection $connection): void
    {
        if ($client->getJobRouterVersion() === $connection->getJobrouterVersion()) {
            return;
        }

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $connection->setJobrouterVersion($client->getJobRouterVersion());

        $connectionRepository = $objectManager->get(ConnectionRepository::class);
        $connectionRepository->update($connection);

        $persistenceManager = $objectManager->get(PersistenceManager::class);
        $persistenceManager->persistAll();
    }
}
