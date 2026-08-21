<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ReportCenterController extends Controller
{
    public function users(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $users = User::with('satuan')
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', '%'.$query.'%')
                        ->orWhere('username', 'like', '%'.$query.'%')
                        ->orWhere('email', 'like', '%'.$query.'%')
                        ->orWhere('jabatan', 'like', '%'.$query.'%');
                });
            })
            ->orderBy('name')
            ->limit(500)
            ->get();

        return response()->json([
            'total' => $users->count(),
            'rows' => $users->map(fn ($u) => [
                'name' => $u->name ?: '-',
                'username' => $u->username ?: '-',
                'email' => $u->email ?: '-',
                'satuan' => $u->satuan?->nama ?: '-',
                'jabatan' => $u->jabatan ?: '-',
                'created' => $u->created_at?->format('d/m/Y H:i') ?: '-',
            ])->values(),
        ]);
    }

    public function activities(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $dari = $request->query('dari');
        $sampai = $request->query('sampai');

        $log = ActivityLog::with('user.satuan')
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('aksi', 'like', '%'.$query.'%')
                        ->orWhere('deskripsi', 'like', '%'.$query.'%')
                        ->orWhere('nama_pengguna', 'like', '%'.$query.'%')
                        ->orWhere('ip_address', 'like', '%'.$query.'%');
                });
            })
            ->when($dari, fn ($q) => $q->whereDate('created_at', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('created_at', '<=', $sampai))
            ->latest('created_at')
            ->limit(500)
            ->get();

        return response()->json([
            'total' => $log->count(),
            'rows' => $log->map(fn ($l) => [
                'waktu' => $l->created_at?->format('d/m/Y H:i:s') ?: '-',
                'pengguna' => $l->nama_pengguna ?: ($l->user?->name ?: '-'),
                'satuan' => $l->user?->satuan?->nama ?: '-',
                'aksi' => $l->aksi ?: '-',
                'deskripsi' => $l->deskripsi ?: '-',
            ])->values(),
        ]);
    }
}
