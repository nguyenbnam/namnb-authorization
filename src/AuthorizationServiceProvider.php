<?php

namespace Namnb\Authorization;

use Illuminate\Routing\Router;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuthorizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-actions')
            ->hasCommands($this->getCommands())
            ->hasMigrations([
                'create_imports_table',
                'create_exports_table',
                'create_failed_import_rows_table',
            ])
            ->hasRoute('web')
            ->hasTranslations()
            ->hasViews();
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
    }
}
