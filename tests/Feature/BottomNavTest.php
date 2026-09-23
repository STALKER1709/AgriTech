<?php

declare(strict_types=1);

use App\Models\User;
use App\View\Components\BottomNav;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The bottom tab bar, checked for the two ways a tab can be wrong before
 * anyone looks at the screen.
 */

/**
 * One tab list per audience the component serves.
 *
 * @return array<string, array<int, array{label: string, href: string, icon: string, current: bool, badge: int|null}>>
 */
function everyTabList(): array
{
    return [
        'visiteur' => (new BottomNav)->tabs(),
        'client' => (new BottomNav(User::factory()->client()->create()))->tabs(),
        'agriculteur' => (new BottomNav(User::factory()->farmer()->create()))->tabs(),
        'administrateur' => (new BottomNav(User::factory()->admin()->create()))->tabs(),
        'compte en attente' => (new BottomNav(User::factory()->awaitingValidation()->create()))->tabs(),
    ];
}

it('never labels a tab with a word that also names a translation file', function () {
    // `__('Validation')` is not the string "Validation": a key without a dot
    // sends the translator looking for a *group* of that name, and a group
    // resolves to the whole file — an array. Windows makes it worse, because
    // the lookup is case-insensitive there: `Validation` finds
    // `lang/fr/validation.php`, and the bar renders an array into
    // htmlspecialchars(). This test fails on every platform, not just the one
    // where the page breaks.
    $groups = collect(File::files(lang_path('fr')))
        ->map(fn ($file): string => Str::lower($file->getFilenameWithoutExtension()))
        ->all();

    expect($groups)->not->toBeEmpty();

    foreach (everyTabList() as $audience => $tabs) {
        foreach ($tabs as $tab) {
            expect($tab['label'])->toBeString("L'onglet « {$audience} » doit porter un libellé texte.");
            expect(Str::lower($tab['label']))->not->toBeIn($groups);
        }
    }
});

it('names every tab icon the way Material Symbols does', function () {
    // The icon component prints the name as a ligature. A Heroicon name such
    // as `squares-2x2` has no ligature, so the font prints the word itself —
    // 128px of text where a 24px glyph belongs.
    foreach (everyTabList() as $audience => $tabs) {
        foreach ($tabs as $tab) {
            expect($tab['icon'])->toMatch('/^[a-z0-9_]+$/', "L'onglet « {$audience} » porte une icône hors Material Symbols.");
        }
    }
});

it('gives every tab a usable destination', function () {
    foreach (everyTabList() as $audience => $tabs) {
        expect($tabs)->not->toBeEmpty($audience);

        foreach ($tabs as $tab) {
            expect($tab['href'])->toStartWith('http');
            expect($tab['badge'])->toBeIn([null, ...range(1, 99)]);
        }
    }
});
