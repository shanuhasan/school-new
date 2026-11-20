<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * "Login as" (impersonation).
 *
 * A Super Admin, already signed in with their OWN password, switches the session to another user
 * to see what that user sees, then switches back. There is no shared/master password: access is
 * authorised by the signed-in Super Admin's session, and every start/stop is written to the log.
 */
class ImpersonationController extends Controller
{
    private const SESSION_KEY = 'impersonator_id';
    private const SESSION_NAME = 'impersonator_name';

    /** Begin impersonating $user. */
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin->hasRole('Super Admin'), 403);

        // Already impersonating: must stop before starting another (one level only)
        if ($request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('dashboard')
                ->with('error', 'You are already signed in as another user. Stop first, then try again.');
        }

        if ($user->is($admin)) {
            return back()->with('error', 'You cannot log in as yourself.');
        }
        if ($user->hasRole('Super Admin')) {
            return back()->with('error', 'You cannot log in as another Super Admin.');
        }

        Log::info('impersonation.start', [
            'impersonator_id' => $admin->id,
            'impersonator_email' => $admin->email,
            'target_id' => $user->id,
            'target_email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // Remember who we really are, then switch. Keep the same session (no regenerate) so the keys persist.
        $request->session()->put(self::SESSION_KEY, $admin->id);
        $request->session()->put(self::SESSION_NAME, $admin->name);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'You are now signed in as ' . $user->name . '.');
    }

    /** Stop impersonating and return to the original Super Admin account. */
    public function stop(Request $request): RedirectResponse
    {
        $originalId = $request->session()->pull(self::SESSION_KEY);
        $request->session()->forget(self::SESSION_NAME);

        if (! $originalId) {
            return redirect()->route('dashboard');
        }

        $original = User::find($originalId);
        if (! $original) {
            // The original account is gone: fall back to a normal logout rather than stay stuck.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Log::info('impersonation.stop', [
            'impersonator_id' => $original->id,
            'target_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        Auth::login($original);

        return redirect()->route('dashboard')->with('success', 'Back to your own account.');
    }
}
