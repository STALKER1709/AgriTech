<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Puts the local demo data back to its out-of-the-box state.
 *
 * Nothing here is meant for a production server: it drops every table and
 * reseeds, so the walkthrough always starts from the same believable state.
 * The confirmation is skipped when --force is passed, which is how scripted
 * setups call it.
 */
final class ResetDemoCommand extends Command
{
    protected $signature = 'agritech:reset-demo {--force : Sans confirmation}';

    protected $description = 'Recrée la base et recharge le jeu de démonstration';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'Cette commande efface toutes les données locales et recharge la démonstration. Continuer ?',
            true,
        )) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true]);

        $this->line(Artisan::output());

        $this->info('Base de démonstration prête. Comptes et mot de passe : voir README.md (§ 4).');

        return self::SUCCESS;
    }
}
