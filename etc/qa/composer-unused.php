<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static function (Configuration $config): Configuration {
    $config->addNamedFilter(NamedFilter::fromString('bramus/monolog-colored-line-formatter'));
    $config->addNamedFilter(NamedFilter::fromString('monolog/monolog'));
    $config->addNamedFilter(NamedFilter::fromString('wyrihaximus/metrics'));
    $config->addNamedFilter(NamedFilter::fromString('wyrihaximus/metrics-tactician'));
    $config->addNamedFilter(NamedFilter::fromString('wyrihaximus/monolog-factory'));
    $config->addNamedFilter(NamedFilter::fromString('wyrihaximus/react-psr-3-stdio'));

    return $config;
};
