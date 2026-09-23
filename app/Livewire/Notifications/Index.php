<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\User;
use App\Support\NotificationPresenter;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Everything the platform has told this account, newest first.
 *
 * Nothing here is generated for the screen: the rows are the database
 * notifications the services already wrote when an order was paid, a
 * publication moderated or a message sent. The screen reads them, groups them
 * by day, and marks them read — it never invents one.
 */
#[Layout('layouts::app')]
#[Title('Notifications')]
class Index extends Component
{
    private const int PAGE_SIZE = 30;

    #[Url]
    public string $category = '';

    #[Url]
    public bool $unreadOnly = false;

    /**
     * The rows are read three times per render — the feed, the unread
     * counter, the chip counts. Livewire only hydrates public properties, so
     * this stays a private memo for the duration of one request.
     *
     * @var Collection<int, DatabaseNotification>|null
     */
    private ?Collection $memo = null;

    public function mount(): void
    {
        abort_unless(Auth::user() instanceof User, 403);
    }

    /**
     * The notifications on screen, grouped by day: "Aujourd'hui", "Hier",
     * then the date itself.
     *
     * @return Collection<string, Collection<int, DatabaseNotification>>
     */
    public function groups(): Collection
    {
        return $this->visible()->groupBy(function (DatabaseNotification $notification): string {
            $at = $notification->created_at?->timezone(config('app.timezone'));

            if ($at === null) {
                return (string) __('Sans date');
            }

            return match (true) {
                $at->isToday() => (string) __('Aujourd\'hui'),
                $at->isYesterday() => (string) __('Hier'),
                default => $at->translatedFormat('d F Y'),
            };
        });
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function visible(): Collection
    {
        $presenter = $this->presenter();

        return $this->rows()
            ->when($this->unreadOnly, fn (Collection $rows) => $rows->whereNull('read_at'))
            ->when(
                $this->category !== '',
                fn (Collection $rows) => $rows->filter(
                    fn (DatabaseNotification $row): bool => $presenter->category($row) === $this->category,
                ),
            )
            ->take(self::PAGE_SIZE)
            ->values();
    }

    public function unreadCount(): int
    {
        return $this->rows()->whereNull('read_at')->count();
    }

    /**
     * How many rows each chip would show, so that a chip never promises a
     * list it cannot fill.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $presenter = $this->presenter();

        $counts = array_fill_keys(array_keys(NotificationPresenter::CATEGORIES), 0);

        foreach ($this->rows() as $notification) {
            $category = $presenter->category($notification);

            if (array_key_exists($category, $counts)) {
                $counts[$category]++;
            }
        }

        return $counts;
    }

    public function presenter(): NotificationPresenter
    {
        return new NotificationPresenter($this->user());
    }

    /**
     * Open a notification: mark it read, then follow it where it leads.
     */
    public function open(string $id): void
    {
        $notification = $this->find($id);

        $notification->markAsRead();

        $url = $this->presenter()->present($notification)['url'];

        if ($url !== null) {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAsRead(string $id): void
    {
        $this->find($id)->markAsRead();
    }

    public function markAllAsRead(): void
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function resetFilters(): void
    {
        $this->reset(['category', 'unreadOnly']);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function rows(): Collection
    {
        if ($this->memo instanceof Collection) {
            return $this->memo;
        }

        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = $this->user()->notifications()->latest()->limit(200)->get();

        return $this->memo = $notifications;
    }

    /**
     * A notification belongs to the account it was sent to, and to no other:
     * the lookup is scoped to the signed-in user rather than to the id alone.
     */
    private function find(string $id): DatabaseNotification
    {
        $notification = $this->user()->notifications()->whereKey($id)->first();

        abort_unless($notification instanceof DatabaseNotification, 404);

        return $notification;
    }

    private function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function render(): mixed
    {
        return view('livewire.notifications.index');
    }
}
