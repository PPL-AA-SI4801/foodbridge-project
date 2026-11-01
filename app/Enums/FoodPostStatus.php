<?php

namespace App\Enums;

enum FoodPostStatus: string
{
    case Tersedia = 'tersedia';
    case Habis = 'habis';
    case Kedaluwarsa = 'kedaluwarsa';
}
