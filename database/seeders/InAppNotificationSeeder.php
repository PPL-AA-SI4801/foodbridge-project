<?php

namespace Database\Seeders;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InAppNotificationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@foodbridge.test')->first();
        $donor = User::where('email', 'donor@foodbridge.test')->first();
        $recipient = User::where('email', 'penerima@foodbridge.test')->first();

        $notifications = [];

        if ($admin) {
            $notifications[] = [
                'user_id' => $admin->id,
                'judul' => 'Akun Menunggu Verifikasi',
                'pesan' => 'Terdapat pendaftar baru yang menunggu verifikasi.',
                'tipe' => 'akun',
                'is_read' => false,
            ];
            $notifications[] = [
                'user_id' => $admin->id,
                'judul' => 'Postingan Makanan Dihapus',
                'pesan' => 'Sebuah postingan makanan telah dihapus oleh administrator.',
                'tipe' => 'post',
                'is_read' => true,
            ];
        }

        if ($donor) {
            $notifications[] = [
                'user_id' => $donor->id,
                'judul' => 'Pengajuan Klaim Baru',
                'pesan' => 'Penerima mengajukan klaim sebanyak 2 porsi pada postingan Nasi Ayam Geprek Tersedia.',
                'tipe' => 'klaim',
                'is_read' => false,
            ];
            $notifications[] = [
                'user_id' => $donor->id,
                'judul' => 'Permintaan Makanan Baru di Request Board',
                'pesan' => 'Panti Penerima Demo membutuhkan "Kebutuhan Beras Mendesak" (5 porsi) sebelum besok.',
                'tipe' => 'request',
                'is_read' => false,
            ];
            $notifications[] = [
                'user_id' => $donor->id,
                'judul' => 'Laporan Makanan',
                'pesan' => 'Terdapat laporan terkait postingan makanan Anda.',
                'tipe' => 'laporan',
                'is_read' => true,
            ];
        }

        if ($recipient) {
            $notifications[] = [
                'user_id' => $recipient->id,
                'judul' => 'Klaim Disetujui',
                'pesan' => 'Klaim Anda untuk makanan "Tempe Goreng Tersedia" telah disetujui oleh donor.',
                'tipe' => 'claim',
                'is_read' => false,
            ];
            $notifications[] = [
                'user_id' => $recipient->id,
                'judul' => 'Klaim Ditolak',
                'pesan' => 'Mohon maaf, klaim Anda untuk makanan "Sayur Asem Habis" ditolak oleh donor.',
                'tipe' => 'klaim',
                'is_read' => true,
            ];
            $notifications[] = [
                'user_id' => $recipient->id,
                'judul' => 'Permintaan Makanan Anda Direspons Donatur',
                'pesan' => 'Donatur Toko Donor Demo memenuhi permintaan "Kebutuhan Beras Mendesak".',
                'tipe' => 'request',
                'is_read' => false,
            ];
        }

        foreach ($notifications as $data) {
            InAppNotification::firstOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'judul' => $data['judul'],
                    'pesan' => $data['pesan'],
                ],
                $data
            );
        }
    }
}
