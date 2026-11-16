<?php

namespace App\Support;

final class NotificationPresenter
{
    /**
     * Resolve the visual treatment for an in-app notification type.
     *
     * @return array{icon: string, label: string, iconClass: string}
     */
    public static function for(?string $type): array
    {
        return match (strtolower(trim((string) $type))) {
            'akun', 'verifikasi', 'verifikasi_akun', 'verification' => [
                'icon' => 'user-check',
                'label' => 'Verifikasi Akun',
                'iconClass' => 'bg-[#DBEAFE] text-[#1E40AF]',
            ],
            'klaim', 'claim', 'klaim_makanan' => [
                'icon' => 'clipboard-check',
                'label' => 'Klaim Makanan',
                'iconClass' => 'bg-[#DCFCE7] text-[#166534]',
            ],
            'laporan', 'report', 'laporan_makanan' => [
                'icon' => 'triangle-alert',
                'label' => 'Laporan',
                'iconClass' => 'bg-[#FEE2E2] text-[#B91C1C]',
            ],
            'makanan', 'postingan', 'post', 'food', 'food_post' => [
                'icon' => 'package',
                'label' => 'Postingan Makanan',
                'iconClass' => 'bg-secondary/10 text-secondary',
            ],
            'request', 'food_request', 'kebutuhan', 'request_board' => [
                'icon' => 'message-square',
                'label' => 'Request Board',
                'iconClass' => 'bg-[#FEF3C7] text-[#92400E]',
            ],
            default => [
                'icon' => 'bell',
                'label' => 'Informasi',
                'iconClass' => 'bg-muted text-muted-foreground',
            ],
        };
    }
}
