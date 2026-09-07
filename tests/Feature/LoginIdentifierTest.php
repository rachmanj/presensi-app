<?php

namespace Tests\Feature;

use App\Support\LoginIdentifier;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    public function test_identifier_with_at_uses_email_field(): void
    {
        $this->assertSame('email', LoginIdentifier::credentialField('admin@arka.local'));
    }

    public function test_identifier_without_at_uses_username_field(): void
    {
        $this->assertSame('username', LoginIdentifier::credentialField('admin'));
    }

    public function test_resolve_credentials_for_email(): void
    {
        $this->assertSame(
            ['email' => 'admin@arka.local', 'password' => 'secret'],
            LoginIdentifier::resolveCredentials('admin@arka.local', 'secret'),
        );
    }

    public function test_resolve_credentials_for_username(): void
    {
        $this->assertSame(
            ['username' => 'admin', 'password' => 'secret'],
            LoginIdentifier::resolveCredentials('admin', 'secret'),
        );
    }
}
