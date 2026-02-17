<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withRules([DeclareStrictTypesRector::class])
    ->withImportNames()
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true/** other options */)
    ->withSets([
        \RectorLaravel\Set\LaravelSetList::LARAVEL_IF_HELPERS,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_TESTING,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_CODE_QUALITY,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_FACTORIES,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ])->withPaths(['app', 'routes', 'tests', 'database', 'config'])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
