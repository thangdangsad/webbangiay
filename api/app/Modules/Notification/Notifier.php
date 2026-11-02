<?php

namespace App\Modules\Notification;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/** Gửi email (SMTP) qua queue; `MAIL_MAILER=log` khi dev. Không dùng SMS. */
class Notifier
{
    public static function mail(string $to, string $subject, string $body): void
    {
        dispatch(function () use ($to, $subject, $body) {
            try {
                Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
            } catch (\Throwable $e) {
                Log::warning('Mail failed', ['to' => $to, 'error' => $e->getMessage()]);
            }
        })->afterCommit();
    }

    /** Gửi email cho nhân viên theo mã vai trò (manager, warehouse, cs, admin). */
    public static function staff(array $roleCodes, string $subject, string $body): void
    {
        \App\Modules\User\Models\StaffUser::where('status', 'active')->whereHas('role', fn ($q) => $q->whereIn('code', $roleCodes))
            ->pluck('email')->each(fn ($email) => self::mail($email, $subject, $body));
    }

    /** 0901234567 / +84901234567 → +84901234567 */
    public static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[\s.\-]/', '', $phone);

        return str_starts_with($p, '0') ? '+84'.substr($p, 1) : $p;
    }
}
