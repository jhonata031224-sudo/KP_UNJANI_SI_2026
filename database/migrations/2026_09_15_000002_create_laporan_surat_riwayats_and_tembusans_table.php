<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom siklus & penyelesaian pada laporan_surats,
     * serta membuat tabel riwayat alur (laporan_surat_riwayats)
     * dan tembusan / view only (laporan_surat_tembusans).
     */
    public function up(): void
    {
        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->unsignedInteger('siklus')->default(1)->after('tindakan');
            $table->string('disposisi_terakhir')->nullable()->after('siklus');
            $table->json('tindakan_terakhir')->nullable()->after('disposisi_terakhir');
            $table->boolean('is_selesai')->default(false)->after('status');
            $table->timestamp('selesai_at')->nullable()->after('is_selesai');
            $table->foreignId('selesai_oleh')->nullable()->after('selesai_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('laporan_surat_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_surat_id')->constrained('laporan_surats')->cascadeOnDelete();
            $table->unsignedInteger('siklus')->default(1);
            $table->string('aksi'); // BUAT_SURAT, KONFIRMASI, TERUSKAN, SURAT_KELUAR, SELESAI
            $table->foreignId('pengirim_satuan_id')->constrained('satuans')->cascadeOnDelete();
            $table->foreignId('penerima_satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('disposisi')->nullable();
            $table->json('tindakan')->nullable();
            $table->text('catatan')->nullable();
            $table->string('lampiran_path')->nullable();
            $table->string('lampiran_nama_asli')->nullable();
            $table->timestamps();
        });

        Schema::create('laporan_surat_tembusans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_surat_id')->constrained('laporan_surats')->cascadeOnDelete();
            $table->foreignId('laporan_surat_riwayat_id')->nullable()->constrained('laporan_surat_riwayats')->nullOnDelete();
            $table->foreignId('satuan_id')->constrained('satuans')->cascadeOnDelete();
            $table->string('jenis')->default('tembusan'); // view_only, tembusan, hasil_rc
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->foreignId('dikonfirmasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_surat_tembusans');
        Schema::dropIfExists('laporan_surat_riwayats');

        Schema::table('laporan_surats', function (Blueprint $table) {
            $table->dropForeign(['selesai_oleh']);
            $table->dropColumn([
                'siklus',
                'disposisi_terakhir',
                'tindakan_terakhir',
                'is_selesai',
                'selesai_at',
                'selesai_oleh',
            ]);
        });
    }
};
