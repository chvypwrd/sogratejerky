<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerProfileRequest;
use App\Notifications\CustomerVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(UpdateCustomerProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $email = Str::lower($data['email']);
        $emailChanged = $email !== Str::lower($user->email);
        $passwordChanged = ! empty($data['password']);

        if (($emailChanged || $passwordChanged) && ! Hash::check($request->input('current_password', ''), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->name = $data['name'];
        $user->email = $email;

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($passwordChanged) {
            $user->password = $data['password'];
        }

        $user->save();

        if ($emailChanged) {
            try {
                $user->notify(new CustomerVerifyEmail);
            } catch (\Throwable $e) {
                Log::warning('Customer verification notification failed after email change', [
                    'user_id' => $user->id,
                    'exception' => get_class($e),
                ]);
            }
        }

        return redirect()
            ->route('account.profile.edit')
            ->with('status', $emailChanged
                ? 'Profile updated. Check your new email address to verify it.'
                : 'Profile updated successfully.');
    }
}
