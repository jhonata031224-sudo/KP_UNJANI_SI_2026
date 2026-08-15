<?php

namespace Database\Seeders;

use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SatuanSeeder extends Seeder
{
    /**
     * Daftar satuan sesuai hasil rapat.
     * Setiap satuan hanya punya SATU akun (tidak ada pembagian Komandan/Piket
     * lagi) — satu orang memegang penuh satuannya, mulai dari input laporan
     * sampai verifikasi/teruskan laporan.
     */
    public function run(): void
    {
        $satuans = [
            // --- ADMIN (Pengelola sistem, bukan satuan operasional) ---
            ['kode' => 'ADMIN', 'username' => 'admin', 'nama' => 'Administrator Sistem', 'kategori' => Satuan::KATEGORI_ADMIN, 'deskripsi' => 'Kelola akun pengguna, satuan, dan permintaan reset password.'],

            // --- SATLAK (Satuan Pelaksana) ---
            ['kode' => 'SATLAKKAL',      'username' => 'satlakkal',   'nama' => 'Satuan Pelaksanaan Penangkalan',        'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Pemantauan & pemulihan (mis. website yang diserang).'],
            ['kode' => 'SATLAKSISOS',    'username' => 'satlaksisos', 'nama' => 'Satuan Pelaksanaan Siber Sosial',   'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Pengelolaan media sosial di daerah.'],
            ['kode' => 'SATLAKDAK',      'username' => 'satlakdak',   'nama' => 'Satuan Pelaksanaan Penindakan',         'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Penanganan aksi cyber: malware, ransomware, serangan.'],
            ['kode' => 'SATLAKDUKTEK',  'username' => 'satlakduktek', 'nama' => 'Satuan Pelaksanaan Dukungan Teknologi', 'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Riset & pengembangan teknologi terkini (AI, drone, dll).'],

            // --- DIR (Direktorat) ---
            ['kode' => 'BINFUNG', 'username' => 'binfung', 'nama' => 'Pembinaan Fungsi',    'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Penempatan personel yang masuk.'],
            ['kode' => 'BINUM',  'username' => 'binum',  'nama' => 'Pembinaan Umum',      'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Pengawasan satuan, lomba internal, personel baru.'],
            ['kode' => 'DIKLAT',  'username' => 'diklat', 'nama' => 'Pendidikan dan Latihan', 'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Pendidikan dan latihan satuan.'],
            ['kode' => 'BINMAT',  'username' => 'binmat', 'nama' => 'Pembinaan Materil',  'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Pengurusan material/perlengkapan satuan.'],

            // --- Koordinasi / Pimpinan ---
            ['kode' => 'WADAN',  'username' => 'wadan',  'nama' => 'Wakil Komandan',       'kategori' => Satuan::KATEGORI_PIMPINAN, 'deskripsi' => 'Penerima laporan dari Satlak.'],
            ['kode' => 'DANPUS', 'username' => 'danpus', 'nama' => 'Komandan Pusat', 'kategori' => Satuan::KATEGORI_PIMPINAN, 'deskripsi' => 'Penerima laporan tertinggi dari seluruh satuan.'],
        ];

        foreach ($satuans as $data) {
            $satuan = Satuan::updateOrCreate(
                ['kode' => $data['kode']],
                collect($data)->except('username')->all()
            );

            // Satu akun per satuan — memegang seluruh alur (input & verifikasi laporan).
            // Dicocokkan lewat satuan_id (bukan username) supaya perubahan username
            // tidak membuat akun baru/duplikat, melainkan meng-update akun yang sudah ada.
            User::updateOrCreate(
                ['satuan_id' => $satuan->id],
                [
                    'name' => $data['nama'],
                    'username' => $data['username'],
                    'email' => $data['username'].'@pussiberad.mil.id',
                    'password' => Hash::make('111'),
                    'jabatan' => null,
                ]
            );
        }
    }
}
