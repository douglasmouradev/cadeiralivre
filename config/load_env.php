<?php

declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Carrega variáveis de ambiente: primeiro `barbershop-saas/.env`, senão `.env` na pasta pai.
 *
 * @param non-falsy-string $projectRoot Caminho absoluto da raiz do projeto (pasta barbershop-saas)
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
