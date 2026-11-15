<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TenancyLocalizationTest extends TestCase
{
    public function test_tenancy_messages_are_available_in_english_and_arabic(): void
    {
        App::setLocale('en');
        $this->assertSame('Signed in successfully.', __('tenancy::messages.logged_in'));

        App::setLocale('ar');
        $this->assertSame('تم تسجيل الدخول بنجاح.', __('tenancy::messages.logged_in'));
    }
}
