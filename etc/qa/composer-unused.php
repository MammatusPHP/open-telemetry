<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\PatternFilter;

return static fn(Configuration $config): Configuration => $config
    ->addPatternFilter(PatternFilter::fromString('/open-telemetry\/.*/'))
;
