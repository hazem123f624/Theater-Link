<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Messages\MessageFactory;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;

class TenantPasswordResetController extends Controller
{
    use HttpResponsesTrait;

    public function requestReset(Request $request)
    {
        $data = $request->validate([
            'subdomain' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $member = $this->member($data['subdomain'], $data['email']);

        if (! $member) {
            return $this->failure(__('tenancy::messages.invalid_credentials'), 422);
        }

        $code = app(OtpService::class)->generate($member, 'forgot_password');
        $token = app()->environment('production') ? Str::random(64) : config('auth_guards.test.token');

        $member->update([
            'change_password_token' => $token,
            'change_password_expired_at' => now()->addMinutes(10),
        ]);

        MessageFactory::make('sms')->send([
            'to' => $member->phone,
            'body' => __('tenancy::messages.password_reset_sms', ['code' => $code]),
        ]);

        return $this->success(__('tenancy::messages.password_reset_code_sent'), [
            'change_password_token' => $token,
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'subdomain' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:4'],
            'change_password_token' => ['required', 'string'],
        ]);

        $member = $this->member($data['subdomain'], $data['email']);
        $this->assertToken($member, $data['change_password_token']);

        app(OtpService::class)->verify($member, $data['otp'], 'forgot_password');

        return $this->success(__('messages.otp_verified'), [
            'change_password_token' => $member->change_password_token,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'subdomain' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:4'],
            'change_password_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $member = $this->member($data['subdomain'], $data['email']);
        $this->assertToken($member, $data['change_password_token']);

        $otp = $member->otps()
            ->where('type', 'forgot_password')
            ->where('is_verified', true)
            ->latest()
            ->first();

        if (! $otp || ! Hash::check($data['otp'], $otp->code)) {
            throw ValidationException::withMessages([
                'otp' => [__('messages.invalid_otp')],
            ]);
        }

        $member->update([
            'password' => $data['password'],
            'change_password_token' => null,
            'change_password_expired_at' => null,
        ]);
        $member->tokens()->delete();

        return $this->success(__('messages.password_reset_successfully'));
    }

    private function member(string $subdomain, string $email): ?TenantMember
    {
        $tenant = Tenant::query()->where('subdomain', Str::lower($subdomain))->first();

        return $tenant?->members()->where('email', Str::lower($email))->where('is_active', true)->first();
    }

    private function assertToken(?TenantMember $member, string $token): void
    {
        if (! $member || ! $member->change_password_token || ! hash_equals($member->change_password_token, $token)) {
            throw ValidationException::withMessages([
                'change_password_token' => [__('messages.invalid_or_expired_token')],
            ]);
        }

        if (! $member->change_password_expired_at || $member->change_password_expired_at->isPast()) {
            throw ValidationException::withMessages([
                'change_password_token' => [__('messages.expired_token')],
            ]);
        }
    }
}
