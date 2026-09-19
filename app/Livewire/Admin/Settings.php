<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\SettingService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paramètres de la plateforme')]
class Settings extends Component
{
    /**
     * Keyed by setting key, so the form binds straight to it.
     *
     * @var array<string, string>
     */
    public array $values = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Setting::class);

        foreach ($this->settings() as $setting) {
            $this->values[$setting->key] = $setting->value;
        }
    }

    /**
     * @return Collection<int, Setting>
     */
    public function settings(): Collection
    {
        return Setting::query()->orderBy('key')->get();
    }

    public function save(SettingService $service): void
    {
        $admin = $this->admin();

        foreach ($this->settings() as $setting) {
            $this->authorize('update', $setting);

            try {
                $service->update($setting, (string) ($this->values[$setting->key] ?? $setting->value), $admin);
            } catch (InvalidArgumentException $exception) {
                $this->addError('values.'.$setting->key, $exception->getMessage());

                return;
            }
        }

        Flux::toast(variant: 'success', text: __('Paramètres enregistrés.'));
    }

    private function admin(): User
    {
        $admin = Auth::user();

        abort_unless($admin instanceof User, 403);

        return $admin;
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings', ['settings' => $this->settings()]);
    }
}
