<?php

namespace App\Enums;

enum Role: string
{
    case Donor = 'donor';
    case Penerima = 'penerima';
    case Administrator = 'administrator';
}
