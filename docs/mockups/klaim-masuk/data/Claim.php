<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DISETUJUI = 'disetujui';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DITOLAK = 'ditolak';

    public const METHOD_AMBIL_SENDIRI = 'ambil_sendiri';
    public const METHOD_KURIR_ONLINE = 'kurir_online';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'food_name',
        'food_icon',
        'portions',
        'recipient_name',
        'recipient_city',
        'method',
        'claim_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'portions' => 'integer',
        ];
    }

    protected function methodLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->method) {
            self::METHOD_KURIR_ONLINE => 'Kurir Online',
            default => 'Ambil Sendiri',
        });
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->status) {
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DITOLAK => 'Ditolak',
            default => 'Menunggu',
        });
    }
}
