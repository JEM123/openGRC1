<?php

namespace App\Providers;

use App\Models\User;
use App\Livewire\CustomSessionGuard;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override the package's SessionGuard component with our custom one
        Livewire::component('filament-inactivity-guard::session-guard', CustomSessionGuard::class);

        // Disable mass assignment protection
        Model::unguard();
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // Only skip the install check if running the installer command
        $isInstaller = false;
        if ($this->app->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            if (isset($argv[1]) && (
                $argv[1] === 'opengrc:install'
            || $argv[1] === 'opengrc:deploy'
            || $argv[1] === 'package:discover'
            || $argv[1] === 'filament:upgrade'
            || $argv[1] === 'vendor:publish'
            )) {
                $isInstaller = true;
            }
        }

        if (! $isInstaller) {
            if (Schema::hasTable('settings')) {

                Config::set('app.name', setting('general.name', 'OpenGRC'));
                Config::set('app.url', setting('general.url', 'https://opengrc.test'));

                config()->set('mail', array_merge(config('mail'), [
                    'driver' => 'smtp',
                    'transport' => 'smtp',
                    'host' => setting('mail.host'),
                    'username' => setting('mail.username'),
                    'password' => setting('mail.password'),
                    'encryption' => setting('mail.encryption'),
                    'port' => setting('mail.port'),
                    'from' => [
                        'address' => setting('mail.from'),
                        'name' => setting('general.name'),
                    ],
                ]));

                // Configure filesystem based on settings
                $storageDriver = setting('storage.driver', 'private');

                // Ensure local disk is always configured
                config()->set('filesystems.disks.local', array_merge(config('filesystems.disks.local', []), [
                    'driver' => 'private',
                    'root' => storage_path('app'),
                    'throw' => false,
                ]));

                if ($storageDriver === 's3') {
                    $s3Key = setting('storage.s3.key');
                    $s3Secret = setting('storage.s3.secret');

                    // Decrypt credentials if they exist and are encrypted
                    try {
                        if (! empty($s3Key)) {
                            $s3Key = Crypt::decryptString($s3Key);
                        }
                        if (! empty($s3Secret)) {
                            $s3Secret = Crypt::decryptString($s3Secret);
                        }
                        config()->set('filesystems.disks.s3', array_merge(config('filesystems.disks.s3', []), [
                            'driver' => 's3',
                            'key' => $s3Key,
                            'secret' => $s3Secret,
                            'region' => setting('storage.s3.region', 'us-east-1'),
                            'bucket' => setting('storage.s3.bucket'),
                            'use_path_style_endpoint' => false,
                        ]));
                    } catch (\Exception $e) {
                        // If decryption fails, log it but don't expose the error
                        \Log::error('Failed to decrypt S3 credentials: '.$e->getMessage());
                        // Fall back to local storage if S3 credentials can't be decrypted
                        $storageDriver = 'private';
                    }
                }

                // Set the default filesystem driver
                config()->set('filesystems.default', $storageDriver);

                // Set session lifetime from settings
                Config::set('session.lifetime', setting('security.session_timeout', 15));
            } else {
                // if table "settings" does not exist
                // Error that app was not installed properly
                abort(500, 'OpenGRC was not installed properly. Please review the
                installation guide at https://docs.opengrc.com to install the app.');
            }
        }

        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['en', 'es', 'fr', 'hr']);
        });

        FilamentColor::register([
            'bg-grcblue' => [
                50 => '#eaf3f7',
                100 => '#d4e7ef',
                200 => '#a9cfe0',
                300 => '#7eb7d1',
                400 => '#1375a0',
                500 => '#106689',
                600 => '#0d5773',
                700 => '#0a485d',
                800 => '#374151',
                900 => '#212a3a',
            ],
            'danger' => [
                50 => '254, 242, 242',
                100 => '254, 226, 226',
                200 => '254, 202, 202',
                300 => '252, 165, 165',
                400 => '248, 113, 113',
                500 => '239, 68, 68',
                600 => '220, 38, 38',
                700 => '185, 28, 28',
                800 => '153, 27, 27',
                900 => '127, 29, 29',
                950 => '69, 10, 10',
            ],
        ]);

    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
