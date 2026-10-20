<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Hash;

/**
 * Shared lazy password-migration for the two legacy auth tables.
 *
 * Login tries bcrypt first (Hash::check). If that fails, it falls back to the
 * model's legacy check (md5 for Staff, plaintext equality for CustomerAccount).
 * If the legacy check succeeds, the password is immediately rehashed to bcrypt
 * and saved, so the account is upgraded transparently on its next login.
 *
 * Models using this trait must implement legacyPasswordMatches() and should
 * override getAuthPasswordName() if their password column isn't "password".
 */
trait LazilyMigratesLegacyPassword
{
    /**
     * Determine if the given plain password matches this account's legacy
     * (pre-bcrypt) stored password.
     */
    abstract public function legacyPasswordMatches(string $plainPassword): bool;

    public function verifyAndMigratePassword(string $plainPassword): bool
    {
        // Hash::check() throws instead of returning false when the stored
        // value isn't actually a bcrypt hash — which is exactly the legacy
        // (md5 / plaintext) case we need to fall through from.
        try {
            if (Hash::check($plainPassword, $this->getAuthPassword())) {
                return true;
            }
        } catch (\RuntimeException) {
            // fall through to the legacy check below
        }

        if (! $this->legacyPasswordMatches($plainPassword)) {
            return false;
        }

        $this->forceFill([
            $this->getAuthPasswordName() => Hash::make($plainPassword),
        ])->save();

        return true;
    }
}
