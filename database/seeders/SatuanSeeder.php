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
            ['kode' => 'SATLAKKAL',      'username' => 'satlak kal',        'nama' => 'Satlak Kal (Penangkalan)',        'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Pemantauan & pemulihan (mis. website yang diserang).'],
            ['kode' => 'SATLAKSISOS',    'username' => 'satlak siber sos',  'nama' => 'Satlak Siber Sos (Siber Sosial)',   'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Pengelolaan media sosial di daerah.'],
            ['kode' => 'SATLAKDAK',      'username' => 'satlak dak',        'nama' => 'Satlak Dak (Penindakan)',         'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Penanganan aksi cyber: malware, ransomware, serangan.'],
            ['kode' => 'SATLAKDUKTEK',  'username' => 'satlak dukteksi',    'nama' => 'Satlak Dukteksi (Dukungan Teknologi)', 'kategori' => Satuan::KATEGORI_SATLAK,      'deskripsi' => 'Riset & pengembangan teknologi terkini (AI, drone, dll).'],

            // --- DIR (Direktorat) ---
            ['kode' => 'BINFUNG', 'username' => 'sdirbinfung', 'nama' => 'Sdir Binfung (Pembinaan Fungsi)',    'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Penempatan personel yang masuk.'],
            ['kode' => 'BINUM',  'username' => 'sdirbinum',  'nama' => 'Sdir Binum (Pembinaan Umum)',      'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Pengawasan satuan, lomba internal, personel baru.'],
            ['kode' => 'DIKLAT',  'username' => 'sdirbindiklat', 'nama' => 'Sdir Bindiklat (Pendidikan & Latihan)', 'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Pendidikan dan latihan satuan.'],
            ['kode' => 'BINMAT',  'username' => 'sdirbinmat', 'nama' => 'Sdir Binmat (Pembinaan Materiil)',  'kategori' => Satuan::KATEGORI_DIREKTORAT, 'deskripsi' => 'Pengurusan material/perlengkapan satuan.'],

            // --- Koordinasi / Pimpinan ---
            ['kode' => 'WADAN',  'username' => 'wadanpussiberad',  'nama' => 'Wadan (Wakil Komandan Pussiberad)',       'kategori' => Satuan::KATEGORI_PIMPINAN, 'deskripsi' => 'Penerima laporan dari Satlak.'],
            ['kode' => 'DANPUS', 'username' => 'danpussiberad', 'nama' => 'Danpus (Komandan Pusat Pussiberad)', 'kategori' => Satuan::KATEGORI_PIMPINAN, 'deskripsi' => 'Penerima laporan tertinggi dari seluruh satuan.'],
        ];

        foreach ($satuans as $data) {
            $satuan = Satuan::updateOrCreate(
                ['kode' => $data['kode']],
                collect($data)->except('username')->all()
            );

            // Email tidak boleh mengandung spasi (format email tidak valid),
            // jadi khusus untuk email dibuat dari username tanpa spasi --
            // login-nya sendiri tetap pakai username asli (boleh berspasi,
            // mis. "satlak kal").
            $emailLocal = str_replace(' ', '', $data['username']);

            // Satu akun per satuan — memegang seluruh alur (input & verifikasi laporan).
            // Dicocokkan lewat satuan_id (bukan username) supaya perubahan username
            // tidak membuat akun baru/duplikat, melainkan meng-update akun yang sudah ada.
            User::updateOrCreate(
                ['satuan_id' => $satuan->id],
                [
                    'name' => $data['nama'],
                    'username' => $data['username'],
                    'email' => $emailLocal.'@pussiberad.mil.id',
                    'password' => Hash::make('111'),
                    'jabatan' => null,
                ]
            );
        }

        // --- Pok Pel (Kelompok Pelayan), langsung di bawah/melayani Danpus ---
        // Akun pengguna SENGAJA tidak dibuat otomatis di sini -- ditambah
        // manual lewat Admin > Daftar Pengguna setelah satuannya tersedia.
        // Kalau mau samain username-nya, isi manual: "Pok analis" untuk
        // POKANALIS dan "urdal" untuk URDAL.
        $satuanPokPel = [
            ['kode' => 'POKANALIS', 'nama' => 'Pok Analis (Kelompok Analis)', 'kategori' => Satuan::KATEGORI_POKPEL, 'deskripsi' => 'Kelompok Pelayan Danpus untuk analisis dan kajian.'],
            ['kode' => 'URDAL',     'nama' => 'Urdal (Urusan Dalam)',         'kategori' => Satuan::KATEGORI_POKPEL, 'deskripsi' => 'Kelompok Pelayan Danpus untuk urusan dalam.'],
        ];

        foreach ($satuanPokPel as $data) {
            Satuan::updateOrCreate(['kode' => $data['kode']], $data);
        }
    }
}
