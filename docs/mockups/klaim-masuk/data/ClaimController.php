<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'method' => (string) $request->query('method', ''),
        ];

        $claims = Claim::query()
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $search = '%'.$filters['q'].'%';

                $query->where(function ($query) use ($search) {
                    $query->where('food_name', 'like', $search)
                        ->orWhere('recipient_name', 'like', $search);
                });
            })
            ->when(array_key_exists($filters['status'], $this->statusOptions()), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(array_key_exists($filters['method'], $this->methodOptions()), function ($query) use ($filters) {
                $query->where('method', $filters['method']);
            })
            ->orderByDesc('claim_date')
            ->orderBy('id')
            ->get();

        return view('claims.index', [
            'claims' => $claims,
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'methodOptions' => $this->methodOptions(),
            'stats' => [
                'waiting' => Claim::where('status', Claim::STATUS_MENUNGGU)->count(),
                'approved' => Claim::where('status', Claim::STATUS_DISETUJUI)->count(),
                'total' => Claim::count(),
            ],
            // Angka ringkasan pagination mengikuti copy pada desain.
            'pagination' => [
                'shown' => $claims->count(),
                'total' => 18,
                'current' => 1,
                'last' => 10,
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            Claim::STATUS_MENUNGGU => 'Menunggu',
            Claim::STATUS_DISETUJUI => 'Disetujui',
            Claim::STATUS_SELESAI => 'Selesai',
            Claim::STATUS_DITOLAK => 'Ditolak',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function methodOptions(): array
    {
        return [
            Claim::METHOD_AMBIL_SENDIRI => 'Ambil Sendiri',
            Claim::METHOD_KURIR_ONLINE => 'Kurir Online',
        ];
    }
}
