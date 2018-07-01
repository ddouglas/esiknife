<?php

namespace ESIK\Providers;

use View;

use Illuminate\Support\{Collection, ServiceProvider};
use Illuminate\Database\Eloquent\Relations\Relation;

use ESIK\Models\ESI\{Alliance, Station, Structure, System, Character, Corporation, MailingList};
use ESIK\Models\SDE\{Constellation, Region};

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'system' => System::class,
            'solar_system' => System::class,
            'station' => Station::class,
            'structure' => Structure::class,
            'alliance' => Alliance::class,
            'character' => Character::class,
            'corporation' => Corporation::class,
            'constellation' => Constellation::class,
            'region' => Region::class,
            'mailing_list' => MailingList::class
        ]);

        View::composer('*', \ESIK\Composers\ScopeComposer::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Collection::macro('recursive', function () {
            return $this->map(function ($value) {
                if (is_array($value)) {
                    return collect($value)->recursive();
                }
                if (is_object($value)) {
                    return collect($value)->recursive();
                }

                return $value;
            });
        });

        Collection::macro('paginate', function( $perPage, $total = null, $page = null, $pageName = 'page' ) {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage( $pageName );

            return new LengthAwarePaginator( $this->forPage( $page, $perPage ), $total ?: $this->count(), $perPage, $page, [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        });
    }
}
