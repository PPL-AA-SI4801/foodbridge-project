<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    public function test_it_forwards_an_account_with_an_allowed_role(): void
    {
        $request = $this->requestFor($this->userWithRole(Role::Administrator));

        $response = (new EnsureUserHasRole)->handle(
            $request,
            fn () => response('allowed'),
            'administrator',
        );

        $this->assertSame('allowed', $response->getContent());
    }

    public function test_it_forbids_an_account_with_another_role(): void
    {
        $request = $this->requestFor($this->userWithRole(Role::Donor));

        $this->assertForbidden($request, 'administrator');
    }

    public function test_it_forbids_a_guest(): void
    {
        $request = $this->requestFor(null);

        $this->assertForbidden($request, 'administrator');
    }

    public function test_it_accepts_any_of_several_roles(): void
    {
        $request = $this->requestFor($this->userWithRole(Role::Penerima));

        $response = (new EnsureUserHasRole)->handle(
            $request,
            fn () => response('allowed'),
            'donor',
            'penerima',
        );

        $this->assertSame('allowed', $response->getContent());
    }

    private function requestFor(?User $user): Request
    {
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function userWithRole(Role $role): User
    {
        $user = new User;
        $user->role = $role;

        return $user;
    }

    private function assertForbidden(Request $request, string ...$roles): void
    {
        try {
            (new EnsureUserHasRole)->handle($request, fn () => response('allowed'), ...$roles);
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());

            return;
        }

        $this->fail('The middleware did not forbid the request.');
    }
}
