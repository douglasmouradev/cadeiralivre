<?php

declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Carrega variáveis de ambiente: primeiro `cadeira-livre/.env`, senão `.env` na pasta pai.
 *
 * @param non-falsy-string $projectRoot Caminho absoluto da raiz do projeto (pasta cadeira-livre)
 */
function app_load_dotenv(string $projectRoot): bool
{
    $candidates = [
        $projectRoot,
        dirname($projectRoot),
    ];

    foreach ($candidates as $dir) {
        if (is_file($dir . '/.env')) {
            Dotenv::createImmutable($dir)->safeLoad();

            return true;
        }
    }

    return false;
}
