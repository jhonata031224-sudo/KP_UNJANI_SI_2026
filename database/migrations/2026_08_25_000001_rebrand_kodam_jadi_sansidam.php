<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rebrand penyebutan "Kodam" jadi "Sansidam" pada nama satuan Kotama
     * (21 satuan yang di-seed lewat KotamaSeeder) beserta nama user yang
     * ikut disalin dari nama satuannya. Migration ini menambal data yang
     * sudah kadung ke-seed sebelum rebrand -- instalasi baru otomatis
     * dapat nama "Sansidam" langsung dari KotamaSeeder yang sudah diubah.
     */
    public function up(): void
    {
        DB::table('satuans')
            ->where('nama', 'like', 'Kodam%')
            ->orderBy('id')
            ->get(['id', 'nama'])
            ->each(function ($row) {
                DB::table('satuans')->where('id', $row->id)->update([
                    'nama' => 'Sansidam'.substr($row->nama, strlen('Kodam')),
                ]);
            });

        DB::table('users')
            ->where('name', 'like', 'Kodam%')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($row) {
                DB::table('users')->where('id', $row->id)->update([
                    'name' => 'Sansidam'.substr($row->name, strlen('Kodam')),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('satuans')
            ->where('nama', 'like', 'Sansidam%')
            ->orderBy('id')
            ->get(['id', 'nama'])
            ->each(function ($row) {
                DB::table('satuans')->where('id', $row->id)->update([
                    'nama' => 'Kodam'.substr($row->nama, strlen('Sansidam')),
                ]);
            });

        DB::table('users')
            ->where('name', 'like', 'Sansidam%')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($row) {
                DB::table('users')->where('id', $row->id)->update([
                    'name' => 'Kodam'.substr($row->name, strlen('Sansidam')),
                ]);
            });
    }
};
