<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Platform parameters an administrator can change from the settings screen.
 *
 * The fee and commission figures are starting points, not researched market
 * rates — see DECISIONS.md for the reservation attached to them.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => Setting::FARMER_REGISTRATION_FEE,
                'value' => '10000',
                'type' => SettingType::Integer,
                'label' => "Frais d'inscription agriculteur (FCFA)",
                'description' => "Montant payé une seule fois par l'agriculteur avant l'examen de son compte.",
            ],
            [
                'key' => Setting::PLATFORM_COMMISSION_RATE,
                'value' => '5',
                'type' => SettingType::Integer,
                'label' => 'Commission de la plateforme (%)',
                'description' => 'Pourcentage prélevé sur chaque vente. Figé sur la sous-commande au moment du paiement.',
            ],
            [
                'key' => Setting::ORDER_CANCEL_AFTER_MINUTES,
                'value' => '30',
                'type' => SettingType::Integer,
                'label' => 'Annulation des commandes non payées (minutes)',
                'description' => 'Délai au-delà duquel une commande restée impayée est annulée automatiquement.',
            ],
            [
                'key' => Setting::PAYMENT_EXPIRATION_MINUTES,
                'value' => '15',
                'type' => SettingType::Integer,
                'label' => "Expiration d'un paiement (minutes)",
                'description' => "Délai au-delà duquel un paiement sans réponse de l'opérateur est considéré comme expiré.",
            ],
            [
                'key' => Setting::PRIOR_MODERATION_ENABLED,
                'value' => '1',
                'type' => SettingType::Boolean,
                'label' => 'Modération a priori des publications',
                'description' => 'Si activée, toute publication passe par « En cours de modération » avant d\'être visible.',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
