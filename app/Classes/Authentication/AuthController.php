<?php

namespace App\Classes\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Authentication\AuthFactory;
use App\Services\Authentication\AuthInterface;
use App\Services\OtpService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

use Modules\Admins\Models\Admins;
use Modules\Admins\Resources\AdminLoginResource;

class AuthController extends Controller
{
    use HttpResponsesTrait;

    protected bool $singleFactorAuth;

    protected AuthInterface $authFactory;

    public string $loginType;

    public string $forgetPasswordType;

    public string $verifyType;

    protected $loginRequest;

    protected $registerRequest;

    protected $forgetPasswordRequest;

    protected $resetPasswordRequest;

    protected $verifyForgotPasswordRequest;

    protected $verifyLoginRequest;

    protected $dto;

    protected $model;

    protected $resource;

    public function __construct(
        string $authFactoryType,
        string $authFactoryMethod = 'sanctum',
        bool $singleFactorAuth = true,
        string $loginType = 'email',
        string $forgetPasswordType = 'email',
        string $verifyType = 'login',
    ) {
        $this->loginType = $loginType;
        $this->forgetPasswordType = $forgetPasswordType;
        $this->verifyType = $verifyType;
        $this->singleFactorAuth = $singleFactorAuth;
        $this->authFactory = AuthFactory::make($authFactoryMethod, $authFactoryType);
        $this->resolveModule($authFactoryType);
    }

    private function resolveModule(string $type): void
    {
        $map = [
            'admins' => [
                'loginRequest' => \App\Classes\Authentication\Types\Admins\LoginRequest::class,
                'forgetPasswordRequest' => \App\Classes\Authentication\Types\Admins\ForgetPasswordRequest::class,
                'resetPasswordRequest' => \App\Classes\Authentication\Types\Admins\ResetPasswordRequest::class,
                'verifyForgotPasswordRequest' => \App\Classes\Authentication\Types\Admins\VerifyForgotPassword::class,
                'dto' => \Modules\Admins\DTOs\AdminsDTO::class,
                'model' => Admins::class,
                'resource' => AdminLoginResource::class,
            ],
            'users' => [
                'loginRequest' => \App\Classes\Authentication\Types\Users\LoginRequest::class,
                'registerRequest' => \App\Classes\Authentication\Types\Users\RegisterRequest::class,
                'verifyLoginRequest' => \App\Classes\Authentication\Types\Users\VerifyOtpRequest::class,
                'forgetPasswordRequest' => \App\Classes\Authentication\Types\Users\ForgetPasswordRequest::class,
                'resetPasswordRequest' => \App\Classes\Authentication\Types\Users\ResetPasswordRequest::class,
                'verifyForgotPasswordRequest' => \App\Classes\Authentication\Types\Users\VerifyForgotPassword::class,
                'dto' => \Modules\Users\DTOs\UsersDTO::class,
                'model' => \Modules\Users\Models\User::class,
                'resource' => \Modules\Users\Resources\UserResource::class,
            ],
            'tenant_members' => [
                'dto' => \Modules\Tenancy\DTOs\TenantMemberDTO::class,
                'model' => \Modules\Tenancy\Models\TenantMember::class,
                'resource' => \Modules\Tenancy\Resources\TenantMemberResource::class,
            ],
        ];

        if (! isset($map[$type])) {
            throw new \InvalidArgumentException("Invalid auth type: {$type}");
        }

        foreach ($map[$type] as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function login()
    {
        $request = app($this->loginRequest);
        $request->validateResolved();
        $credentials = $request->only($this->loginType, 'password');

        // Registration and login accept a local phone plus country code.
        // Authenticate against the normalized E.164 value when available.
        if ($this->loginType === 'phone' && $request->filled('full_phone')) {
            $credentials['phone'] = $request->input('full_phone');
        }

        $authData = $this->authFactory->login(request: $credentials, createToken: $this->singleFactorAuth);

        $resource = new $this->resource($authData['user']);
        if ($this->singleFactorAuth) {
            $resource->token = $authData['token'] ?? null;
            $resource->token_type = $authData['token_type'] ?? 'Bearer';
            $resource->expires_in = $authData['expires_in'] ?? null;
        }

        return $this->success(__('messages.welcome').' '.($resource->name ?? ''), data: $resource);
    }

    public function verifyLoginOtp()
    {
        $request = app($this->verifyLoginRequest);
        $request->validateResolved();

        $request = $request->validated();
        $user = ($this->model)::where('phone', $request['phone'])->firstOrFail();
        $authData = app(OtpService::class)->verify(model: $user, code: $request['otp'], type: $this->verifyType, withToken: true);
        $resource = new $this->resource(($this->dto)::fromModel($user));

        $resource->token = $authData['token'] ?? null;
        $resource->token_type = $authData['token_type'] ?? 'Bearer';
        $resource->expires_in = $authData['expires_in'] ?? null;

        return $this->success(__('messages.welcome').($resource->name ?? ''), data: $resource);
    }

    public function resendOtp()
    {
        $request = request()->all();
        $identifier = $request['full_phone'] ?? $request[$this->loginType] ?? null;

        if (! $identifier) {
            return $this->failure(__('messages.identifier_is_required'), 422);
        }

        $type = request('type', $this->verifyType);

        if (\Illuminate\Support\Facades\Cache::has("otp:cache:{$type}:{$identifier}")) {
            $code = app(OtpService::class)->generateByCache($identifier, $type);
        } else {
            ($this->model)::where($this->loginType, $identifier)->firstOrFail();
            $code = app(OtpService::class)->generate(model: ($this->model)::where($this->loginType, $identifier)->firstOrFail(), type: $type);
        }

        return $this->success(__('messages.otp_resent_successfully'), data: [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    public function refreshToken()
    {
        $data = $this->authFactory->refreshToken();

        return $this->success('messages.token_issued', data: $data);
    }

    public function logout()
    {
        $this->authFactory->logout();

        return $this->success('messages.logged_out', data: []);
    }

    public function forgetPassword()
    {
        $request = app($this->forgetPasswordRequest);
        $request->validateResolved();
        $request = $request->validated();
        $token = $this->authFactory->forgotPassword(request: $request, type: $this->forgetPasswordType);

        return $this->success('messages.password_reset_code_sent', data: [
            'change_password_token' => $token,
        ]);
    }

    public function verifyForgotPassword()
    {
        $request = app($this->verifyForgotPasswordRequest);
        $request->validateResolved();
        $request = $request->validated();

        $user = ($this->model)::where($this->forgetPasswordType, $request[$this->forgetPasswordType])->firstOrFail();

        if ($user->change_password_token != $request['change_password_token'] || $user->change_password_expired_at->isPast()) {
            throw ValidationException::withMessages([
                'change_password_token' => [__('messages.invalid_or_expired_token')],
            ]);
        }

        app(OtpService::class)->verify($user, $request['otp'], 'forgot_password');

        return $this->success('messages.otp_verified', data: [
            'change_password_token' => $user->change_password_token,
        ]);
    }

    public function resetPassword()
    {
        $request = app($this->resetPasswordRequest);
        $request->validateResolved();
        $request = $request->validated();

        $this->authFactory->resetPassword(request: $request, type: $this->forgetPasswordType);

        return $this->success('messages.password_reset_successfully', data: []);
    }
}
