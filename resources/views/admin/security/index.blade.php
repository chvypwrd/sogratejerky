@extends('admin.layouts.app')

@section('title', 'Security')

@section('content')
<div class="mb-4">
    <h1>Security</h1>
    <p class="text-muted">Manage your account security and two-factor authentication.</p>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Two-Factor Authentication</h5>
    </div>
    <div class="card-body">
        @if ($enabled)
            <div class="alert alert-success">
                Two-factor authentication is <strong>enabled</strong> on your account.
            </div>

            <p class="text-muted mb-3">
                When two-factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application.
            </p>

            @if (session('recovery_codes'))
                <div class="alert alert-warning">
                    <strong>Your recovery codes:</strong>
                    <div class="mt-2 font-monospace small">
                        @foreach (session('recovery_codes') as $code)
                            {{ $code }}<br>
                        @endforeach
                    </div>
                    <small>Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor authentication device is lost.</small>
                </div>
            @endif

            @if (session('showing_qr_code'))
                <div class="mb-4">
                    <p>Scan the following QR code using your phone's authenticator application:</p>
                    <div class="mb-3">
                        {!! request()->user()->twoFactorQrCodeSvg() !!}
                    </div>
                    <p class="small text-muted">
                        Or enter this secret key manually: <code>{{ decrypt($user->two_factor_secret) }}</code>
                    </p>

                    <form method="POST" action="{{ url('/admin/two-factor/confirm') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Authentication Code</label>
                            <input type="text" name="code" id="code" class="form-control" autocomplete="one-time-code" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ url('/admin/two-factor/enable') }}">
                @csrf
                <button type="submit" class="btn btn-warning">View QR Code & Enable</button>
            </form>

            @if (session('showing_recovery_codes'))
                <form method="POST" action="{{ url('/admin/two-factor/recovery-codes') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">Regenerate Recovery Codes</button>
                </form>
            @endif

            <form method="POST" action="{{ url('/admin/two-factor/disable') }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Disable Two-Factor Authentication</button>
            </form>
        @else
            <p class="text-muted mb-3">
                Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to sign in.
            </p>

            <form method="POST" action="{{ url('/admin/two-factor/enable') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Enable Two-Factor Authentication</button>
            </form>
        @endif
    </div>
</div>
@endsection
