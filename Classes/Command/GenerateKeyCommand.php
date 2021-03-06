<?php

declare(strict_types=1);

/*
 * This file is part of the "jobrouter_connector" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Brotkrueml\JobRouterConnector\Command;

use Brotkrueml\JobRouterConnector\Exception\KeyGenerationException;
use Brotkrueml\JobRouterConnector\Service\KeyGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class GenerateKeyCommand extends Command
{
    private const EXIT_CODE_OK = 0;
    private const EXIT_CODE_KEY_GENERATION_ERROR = 1;

    /** @var KeyGenerator */
    private $keyGenerator;

    public function __construct(KeyGenerator $keyGenerator)
    {
        $this->keyGenerator = $keyGenerator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generates a random key for encrypting and decrypting connection passwords');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputStyle = new SymfonyStyle($input, $output);

        try {
            $this->keyGenerator->generateAndStoreKey();
        } catch (KeyGenerationException $e) {
            $outputStyle->error($e->getMessage());

            return self::EXIT_CODE_KEY_GENERATION_ERROR;
        }

        $outputStyle->success('Key was generated successfully');

        return self::EXIT_CODE_OK;
    }
}
