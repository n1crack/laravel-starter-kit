<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Pest\Browser\Api\PendingAwaitablePage;

/**
 * Waits until the users table renders exactly the given names, top to bottom,
 * and returns whatever order was actually rendered last.
 *
 * Polling in the page keeps the assertion strict while absorbing the gap
 * between the click and React committing the re-render.
 *
 * @param  array<int, string>  $expected
 * @return array<int, string>
 */
function renderedUserNames(PendingAwaitablePage $page, array $expected): array
{
    $target = json_encode($expected, JSON_THROW_ON_ERROR);

    $names = $page->script(<<<JS
        async () => {
            const target = JSON.stringify({$target});
            const read = () => Array.from(document.querySelectorAll('table tbody tr'))
                .map((row) => row.querySelector('td').textContent.trim());

            let names = [];

            for (let attempt = 0; attempt < 50; attempt++) {
                names = read();

                if (JSON.stringify(names) === target) {
                    return names;
                }

                await new Promise((resolve) => setTimeout(resolve, 100));
            }

            return names;
        }
    JS);

    return is_array($names) ? array_map(strval(...), array_values($names)) : [];
}

it('sorts the users table by name in both directions', function (): void {
    $admin = User::factory()->create([
        'name' => 'Zeynep Kaya',
        'created_at' => now()->subMinutes(3),
    ]);
    $admin->assignRole(RoleEnum::Admin);

    User::factory()->create([
        'name' => 'Linus Torvalds',
        'created_at' => now()->subMinute(),
    ]);
    User::factory()->create([
        'name' => 'Ada Lovelace',
        'created_at' => now()->subMinutes(2),
    ]);
    User::factory()->create([
        'name' => 'Grace Hopper',
        'created_at' => now()->subMinutes(4),
    ]);

    $this->actingAs($admin);

    $page = visit(route('admin.users.index', absolute: false));

    $newestFirst = ['Linus Torvalds', 'Ada Lovelace', 'Zeynep Kaya', 'Grace Hopper'];
    $ascending = ['Ada Lovelace', 'Grace Hopper', 'Linus Torvalds', 'Zeynep Kaya'];
    $descending = array_reverse($ascending);

    // The controller returns newest first, so the table does not arrive sorted by name.
    expect(renderedUserNames($page, $newestFirst))->toBe($newestFirst);

    $page->click('Name');

    expect(renderedUserNames($page, $ascending))->toBe($ascending);

    $page->click('Name');

    expect(renderedUserNames($page, $descending))->toBe($descending);

    $page->assertNoJavascriptErrors()->assertNoConsoleLogs();
});
