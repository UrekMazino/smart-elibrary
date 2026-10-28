<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Administration → Accounts → Roles: the access matrix shows modules first, each with a switch per role (its access
 * permission), and locks what's inside a module for a role whose module is off. Its toggle sets a state as well as
 * flipping one, so the two-step "turn the module on, then allow" can't flip a cell changed meanwhile.
 */
class RolesMatrixTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    private function superAdmin(): User
    {
        return tap(User::factory()->create(['usertype' => 'Administrator']))->assignRole('super_admin');
    }

    public function test_modules_lead_with_their_switch_and_lock_what_is_inside_while_off()
    {
        $role = Role::findByName('cataloging_admin')->id;
        $doc = new DOMDocument();
        @$doc->loadHTML($this->actingAs($this->superAdmin())->get('/admin/roles')->assertOk()->getContent());
        $x = new DOMXPath($doc);
        $switch = fn ($module) => $x->query("//tr[@data-module='$module']//button[contains(@class,'mx-switch')][@data-role='$role']")->item(0);
        $count = fn ($module) => trim($x->query("//tr[@data-module='$module']//span[contains(@class,'mod-count')][@data-role='$role']")->item(0)->textContent);
        $locked = fn ($module) => $x->query("//tr[contains(@class,'mx-perm')][@data-module='$module']/td[contains(@class,'locked')][button[@data-role='$role']]")->length;

        // Cataloging is on for Cataloging Admin: nothing inside is locked, and the row counts what is allowed.
        $this->assertSame('true', $switch('cataloging')->getAttribute('aria-checked'));
        $this->assertSame(0, $locked('cataloging'));
        $this->assertSame('6 of 9', $count('cataloging'));

        // Utilities is off for it: all 8 inside are locked, and its seeded "Create MARC template" waits.
        $this->assertSame('false', $switch('utilities')->getAttribute('aria-checked'));
        $this->assertSame(8, $locked('utilities'));
        $this->assertSame('1 waiting', $count('utilities'));

        // Ban / Block Patrons works without Accounts & Patrons (Inquiries and Transactions use it): for Circulation,
        // whose Accounts module is off, it is in effect, not locked or waiting.
        $circulation = Role::findByName('circulation_admin')->id;
        $ban = Permission::findByName('manage.patron.status')->id;
        $banCell = $x->query("//tr[contains(@class,'mx-perm')][@data-module='accounts-patrons']/td[button[@data-perm='$ban'][@data-role='$circulation']]")->item(0);
        $this->assertStringNotContainsString('locked', $banCell->getAttribute('class'));
        $this->assertSame('1 of 4', trim($x->query("//tr[@data-module='accounts-patrons']//span[contains(@class,'mod-count')][@data-role='$circulation']")->item(0)->textContent));

        // A module of one permission is only its switch; Dashboard's two permissions stand on their own, no switch.
        $this->assertNotNull($switch('news'));
        $this->assertSame(0, $x->query("//tr[contains(@class,'mx-perm')][@data-module='news']")->length);
        $this->assertNull($switch('dashboard'));
        $this->assertSame(2, $x->query("//tr[contains(@class,'mx-perm')][@data-module='dashboard']")->length);
    }

    public function test_the_toggle_sets_a_state_as_well_as_flipping_one()
    {
        $role = Role::findByName('cataloging_admin');
        $admin = $this->superAdmin();
        $post = fn (array $state = []) => $this->actingAs($admin)->postJson('/admin/role-permissions/toggle',
            ['role_id' => $role->id, 'permission_id' => Permission::findByName('access.utilities')->id] + $state);

        $post(['granted' => 1])->assertOk()->assertJson(['granted' => true]);
        $post(['granted' => 1])->assertOk()->assertJson(['granted' => true]);   // asked twice (a page left open): stays on
        $this->assertTrue($role->fresh()->hasPermissionTo('access.utilities'));

        $post()->assertOk()->assertJson(['granted' => false]);                  // no state: flips, as before
        $post(['granted' => 0])->assertOk()->assertJson(['granted' => false]);
        $this->assertFalse($role->fresh()->hasPermissionTo('access.utilities'));
    }
}
