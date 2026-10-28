<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StatisticsWatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Administration → Accounts → Statistics watch (StatisticsWatchController, App\Services\StatisticsWatch;
 * RANKING_INTEGRITY_PLAN.md step 2.6): spikes on materials, unusual accounts and sign-up bursts on a day, and taking
 * patrons out of the top-10 lists until they are put back.
 */
class StatisticsWatchTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    private int $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        $this->type = DB::table('materials')->insertGetId(['name' => 'Books']);
        $this->travelTo(Carbon::parse('2026-10-28 15:30'));
    }

    private function staff(string $role): User
    {
        return tap(User::factory()->create(['usertype' => $role === 'developer' ? 'Developer' : 'Administrator']))->assignRole($role);
    }

    private function patron(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['usertype' => 'Patron']);
    }

    private function record(string $title): int
    {
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->type, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => $title, 'material_type_id' => $this->type,
            'is_published' => 1, 'created_at' => now()]);
        return $id;
    }

    /** $count guests viewing the material at $at: each guest row is one viewer. */
    private function guestViews(int $catalogId, int $count, Carbon $at): void
    {
        DB::table('views')->insert(array_fill(0, $count, ['catalog_id' => $catalogId, 'is_guest' => 'YES', 'created_at' => $at]));
    }

    private function download(int $catalogId, User $user, int $times = 1): void
    {
        DB::table('downloads')->insert(array_fill(0, $times, ['catalog_id' => $catalogId, 'user_id' => $user->id, 'created_at' => now()->subHour()]));
    }

    public function test_super_admins_and_developers_have_it_other_staff_do_not()
    {
        $this->actingAs($this->staff('super_admin'))->get('/admin/statistics-watch')->assertOk()
            ->assertSee('STATISTICS WATCH')->assertSee('No material had an unusual day.')->assertSee('No account is out of the rankings.');
        $this->get('/admin/accounts')->assertSee('Statistics watch')->assertSee('Nothing unusual today');
        $this->actingAs($this->staff('developer'))->get('/admin/statistics-watch')->assertOk();

        $this->actingAs($this->staff('system_admin'))->get('/admin/statistics-watch')->assertForbidden();
        $this->post('/admin/statistics-watch/exclusions', ['users' => [$this->patron()->id]])->assertForbidden();
        $this->get('/admin/accounts')->assertOk()->assertDontSee('Statistics watch');
        $this->assertSame(0, DB::table('ranking_exclusions')->count());
    }

    public function test_a_material_with_far_more_viewers_or_readers_than_usual_is_a_spike()
    {
        $spike = $this->record('Coconut sugar');     // a viewer a day, then 12 today
        $steady = $this->record('Rice tea');         // 12 a day, today too
        $quiet = $this->record('Mung bean flour');   // 3 today and none before: too few to matter
        for ($day = 1; $day <= StatisticsWatch::BASELINE_DAYS; $day++) {
            $this->guestViews($spike, 1, now()->subDays($day));
            $this->guestViews($steady, 12, now()->subDays($day));
        }
        $this->guestViews($spike, 12, now()->subHour());
        $this->guestViews($steady, 12, now()->subHour());
        $this->guestViews($quiet, 3, now()->subHour());
        // Ten accounts downloading a material nobody downloaded before: a new favourite, or a ring of accounts.
        $ring = $this->record('Fish sauce');
        $readers = User::factory()->count(10)->create(['usertype' => 'Patron']);
        $readers->each(fn ($reader) => $this->download($ring, $reader));

        $spikes = StatisticsWatch::spikes(today());
        $this->assertEquals([[$ring, 'downloads', 10, null], [$spike, 'views', 12, 12]],
            $spikes->map(fn ($s) => [$s->catalog_id, $s->what, $s->count, $s->times])->all());
        $this->assertEquals([1, 12, 0], [$spikes[1]->usual, $spikes[1]->guests, $spikes[1]->accounts]);
        $this->assertEqualsCanonicalizing($readers->pluck('id')->all(), $spikes[0]->people->keys()->all());

        $admin = $this->staff('super_admin');
        $this->actingAs($admin)->get('/admin/statistics-watch')->assertOk()
            ->assertSeeInOrder(['Materials with a spike', 'Fish sauce', 'none before', 'Take all 10 out', 'Coconut sugar', '12×', 'Unusual accounts'])
            ->assertDontSee('Rice tea')->assertDontSee('Mung bean flour');

        // Its accounts taken out, the downloads spike is gone; the guests' one stays.
        StatisticsWatch::exclude($readers->pluck('id')->all(), $admin, null);
        $this->assertSame(['views'], StatisticsWatch::spikes(today())->pluck('what')->all());
    }

    public function test_accounts_that_download_or_rate_far_more_than_a_reader_does_are_flagged()
    {
        $records = collect(range(1, 10))->map(fn ($i) => $this->record("Material $i"));
        $downloader = $this->patron(['name' => 'Dee Downloader', 'created_at' => now()->subDays(2)]);
        $rater = $this->patron(['name' => 'Ray Rater', 'created_at' => now()->subYear()]);
        $reader = $this->patron(['name' => 'Rhea Reader']);   // 19 downloads and 9 ratings: just under

        $this->download($records[0], $downloader, StatisticsWatch::ACCOUNT_DOWNLOADS);
        $this->download($records[0], $reader, StatisticsWatch::ACCOUNT_DOWNLOADS - 1);
        foreach ($records as $i => $id) {
            DB::table('ratings')->insert(['catalog_id' => $id, 'user_id' => $rater->id, 'rating' => 5, 'created_at' => now()]);
            if ($i) DB::table('ratings')->insert(['catalog_id' => $id, 'user_id' => $reader->id, 'rating' => 4, 'created_at' => now()]);
        }

        $accounts = StatisticsWatch::accounts(today());
        $this->assertSame(['Dee Downloader', 'Ray Rater'], $accounts->pluck('name')->all());   // busiest first
        $this->assertSame([20, 1, 0, true], [$accounts[0]->downloads, $accounts[0]->materials, $accounts[0]->ratings, $accounts[0]->fresh]);
        $this->assertFalse($accounts[1]->fresh);

        $this->actingAs($this->staff('super_admin'))->get('/admin/statistics-watch')->assertOk()
            ->assertSeeInOrder(['Unusual accounts', 'Dee Downloader', 'New', '20', 'Ray Rater', 'Sign-ups'])
            ->assertDontSee('Rhea Reader');
        $this->get('/admin/statistics-watch?day=' . today()->subDay()->toDateString())->assertSee('No account did anything unusual.');
    }

    public function test_an_hour_with_many_sign_ups_is_a_burst_listed_with_its_accounts()
    {
        for ($day = 1; $day <= StatisticsWatch::BASELINE_DAYS; $day++) {   // a usual month: one sign-up a day
            $this->patron(['created_at' => now()->subDays($day)]);
        }
        $burst = collect(range(1, 12))->map(fn ($i) => $this->patron(['name' => "Bot $i", 'email' => "bot$i@mailinator.test", 'created_at' => today()->setTime(14, $i)]));
        $burst[0]->forceFill(['google_id' => '42'])->save();
        foreach (range(1, 3) as $i) {   // 10 AM: three, nothing unusual
            $this->patron(['created_at' => today()->setTime(10, $i)]);
        }
        $this->download($this->record('Coconut sugar'), $burst[1]);   // one has downloaded already

        $signUps = StatisticsWatch::signUps(today());
        $this->assertEquals([15, 1, 1], [$signUps->total, $signUps->usualDay, $signUps->bursts->count()]);
        $this->assertSame([14, 12], [$signUps->bursts[0]->from->hour, $signUps->bursts[0]->count]);
        $this->assertSame(1, $signUps->bursts[0]->people[$burst[1]->id]->downloads);
        $this->assertTrue($signUps->bursts[0]->people[$burst[0]->id]->google);

        $this->actingAs($this->staff('super_admin'))->get('/admin/statistics-watch')->assertOk()
            ->assertSeeInOrder(['2 PM–3 PM', '12 sign-ups', 'Take all 12 out', 'Bot 1', 'Google', 'bot1@mailinator.test', 'bot12@mailinator.test'])
            ->assertDontSee('No hour had an unusual number of sign-ups.');
    }

    public function test_taking_accounts_out_takes_their_counts_out_of_the_lists_until_they_are_put_back()
    {
        $admin = $this->staff('super_admin');
        $gamed = $this->record('Coconut sugar');
        $fair = $this->record('Rice tea');
        $socks = collect([$this->patron(), $this->patron(), $this->patron()]);
        $socks->each(fn ($sock) => $this->download($gamed, $sock));
        $this->download($fair, $this->patron());
        $this->download($fair, $this->patron());
        $top = fn () => array_column(getMostDownloaded(), 'title');
        $this->assertSame(['Coconut sugar', 'Rice tea'], $top());

        // Staff accounts are skipped; the lists change at once, not when their 10-minute cache runs out.
        $this->actingAs($admin)->from('/admin/statistics-watch')->post('/admin/statistics-watch/exclusions', [
            'users' => $socks->pluck('id')->push($admin->id)->all(), 'reason' => 'Three new accounts, one material',
        ])->assertRedirect('/admin/statistics-watch')
            ->assertSessionHas('success', '3 accounts are out of the top-10 lists: their views, downloads and ratings no longer count.');
        $this->assertSame(['Rice tea'], $top());
        $this->assertSame(3, DB::table('ranking_exclusions')->whereNull('lifted_at')
            ->where(['excluded_by' => $admin->id, 'reason' => 'Three new accounts, one material'])->count());
        $this->get('/admin/statistics-watch')
            ->assertSeeInOrder(['Out of the rankings now', $socks[2]->email, 'by ' . $admin->name, 'Three new accounts, one material', 'Put back']);

        $this->post('/admin/statistics-watch/exclusions', ['users' => [$socks[0]->id]])
            ->assertSessionHas('success', 'Nothing changed: they are out of the top-10 lists already.');

        // Put back, an account counts again; the row keeps who took it out and who put it back.
        $this->delete('/admin/statistics-watch/exclusions', ['user' => $socks[0]->id])
            ->assertSessionHas('success', $socks[0]->name . ' is back in the top-10 lists.');
        $this->assertSame(['Rice tea', 'Coconut sugar'], $top());
        $this->assertDatabaseHas('ranking_exclusions', ['user_id' => $socks[0]->id, 'excluded_by' => $admin->id, 'lifted_by' => $admin->id]);
        $this->assertSame(2, DB::table('ranking_exclusions')->whereNull('lifted_at')->count());
    }

    public function test_the_dashboard_and_accounts_say_what_to_look_at_until_it_is_dealt_with()
    {
        $this->seedInstallData();
        $burst = collect(range(1, StatisticsWatch::BURST_AT_LEAST))->map(fn ($i) => $this->patron(['created_at' => today()->setTime(9, $i)]));
        $admin = $this->staff('super_admin');

        $this->assertSame(1, StatisticsWatch::toCheck(today()));
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSeeInOrder(['Needs attention', 'Statistics watch', 'Unusual activity today']);
        $this->get('/admin/accounts')->assertSee('1 thing to look at today');

        StatisticsWatch::exclude($burst->pluck('id')->all(), $admin, null);
        $this->assertSame(0, StatisticsWatch::toCheck(today()));
        $this->get('/admin/accounts')->assertSee('Nothing unusual today');

        $this->actingAs($this->staff('system_admin'))->get('/admin/dashboard')->assertOk()->assertDontSee('Statistics watch');
    }

    /** $count views by the guest with this hash, from $at a minute apart, over the records given in turn. */
    private function guestHashViews(string $hash, int $count, Carbon $at, array $records): void
    {
        DB::table('views')->insert(array_map(fn ($i) => ['catalog_id' => $records[$i % count($records)], 'is_guest' => 'YES',
            'visitor_hash' => $hash, 'created_at' => $at->copy()->addMinutes(intdiv($i * 30, $count))], range(0, $count - 1)));
    }

    public function test_unusual_guests_are_listed_and_their_addresses_show_to_developer_and_super_admin_only()
    {
        $records = [$this->record('Coconut sugar'), $this->record('Rice tea')];
        $bot = str_repeat('ab12', 8);
        $this->guestHashViews($bot, viewCapPerHour(), today()->setTime(14, 0), $records);   // the cap, within the hour
        $this->guestHashViews(str_repeat('cd34', 8), StatisticsWatch::GUEST_VIEWS - 1, today()->setTime(9, 0), $records);   // just under
        DB::table('visitor_addresses')->insert(['visitor_hash' => $bot, 'ip_address' => '203.0.113.7', 'first_seen_at' => now(), 'last_seen_at' => now()]);

        $guests = StatisticsWatch::guests(today(), addresses: true);
        $this->assertEquals([['ab12ab12', 120, true, '203.0.113.7']], $guests->map(fn ($g) => [$g->code, $g->views, $g->capped, $g->address])->all());
        $this->assertNull(StatisticsWatch::guests(today())->first()->address);   // not even loaded without the right role
        $this->assertSame(3, StatisticsWatch::toCheck(today()));   // the guest, and the two materials it made spike

        foreach (['super_admin', 'developer'] as $role) {
            $this->actingAs($this->staff($role))->get('/admin/statistics-watch')->assertOk()
                ->assertSeeInOrder(['Unusual guests', 'Guest ab12ab12', 'At the cap', '120', '2:00 PM', '203.0.113.7', 'Sign-ups'])
                ->assertSeeInOrder(['Materials with a spike', 'The guests’ addresses (1)', '203.0.113.7', '60 guests', 'Unusual accounts'])
                ->assertDontSee('Guest cd34cd34');
        }

        // Another role given the watch sees the guests, not where they are.
        $watcher = $this->staff('system_admin');
        $watcher->givePermissionTo('access.statistics.watch');
        $this->actingAs($watcher)->get('/admin/statistics-watch')->assertOk()
            ->assertSee('Guest ab12ab12')->assertDontSee('203.0.113.7')->assertDontSee('The guests’ addresses');
    }

    public function test_guests_addresses_and_hashes_are_deleted_after_90_days()
    {
        $record = $this->record('Coconut sugar');
        $old = now()->subDays(91);
        DB::table('visitor_addresses')->insert([
            ['visitor_hash' => str_repeat('a', 32), 'ip_address' => '203.0.113.1', 'first_seen_at' => $old, 'last_seen_at' => now()->subDay()],
            ['visitor_hash' => str_repeat('b', 32), 'ip_address' => '203.0.113.2', 'first_seen_at' => now()->subDays(89), 'last_seen_at' => now()],
        ]);
        DB::table('views')->insert([
            ['catalog_id' => $record, 'is_guest' => 'YES', 'visitor_hash' => str_repeat('a', 32), 'created_at' => $old],
            ['catalog_id' => $record, 'is_guest' => 'YES', 'visitor_hash' => str_repeat('a', 32), 'created_at' => now()->subDay()],
        ]);

        $this->artisan('visitors:forget')->expectsOutput('Deleted 1 guest address and 1 hash older than 90 days.')->assertSuccessful();
        $this->assertSame(['203.0.113.2'], DB::table('visitor_addresses')->pluck('ip_address')->all());
        $this->assertSame([null, str_repeat('a', 32)], DB::table('views')->orderBy('created_at')->pluck('visitor_hash')->all());
        $this->assertSame(2, DB::table('views')->count());   // the views still count

        $this->artisan('schedule:list')->expectsOutputToContain('visitors:forget')->assertSuccessful();   // nightly
    }

    public function test_another_day_can_be_watched_but_not_one_to_come()
    {
        $this->assertSame('2026-10-25', StatisticsWatch::day('2026-10-25')->toDateString());
        foreach ([null, 'yesterday', '2026-02-31', '2026-10-29', ['2026-10-25']] as $value) {
            $this->assertTrue(StatisticsWatch::day($value)->isToday());
        }

        $this->actingAs($this->staff('super_admin'))->get('/admin/statistics-watch?day=2026-10-25')->assertOk()
            ->assertSee('What was unusual on Oct 25, 2026')->assertSee('day=2026-10-26', false);
        $this->get('/admin/statistics-watch?day[]=x')->assertOk()->assertSee('What was unusual today');
    }
}
