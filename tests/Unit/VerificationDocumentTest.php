<?php

namespace Tests\Unit;

use App\Models\VerificationDocument;
use PHPUnit\Framework\TestCase;

class VerificationDocumentTest extends TestCase
{
    public function test_it_labels_each_supported_document_type(): void
    {
        $labels = [
            'nib' => 'NIB',
            'akta_pendirian' => 'Akta Pendirian',
            'izin_operasional' => 'Izin Operasional',
            'lainnya' => 'Dokumen Lainnya',
        ];

        foreach ($labels as $jenisDokumen => $label) {
            $document = new VerificationDocument(['jenis_dokumen' => $jenisDokumen]);

            $this->assertSame($label, $document->jenisDokumenLabel());
        }
    }

    public function test_it_falls_back_for_an_unknown_document_type(): void
    {
        $document = new VerificationDocument(['jenis_dokumen' => 'tidak_dikenal']);

        $this->assertSame('Dokumen', $document->jenisDokumenLabel());
    }
}
