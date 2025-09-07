<?php

namespace App\Enum;

enum Compression: string
{
    case Zip = 'zip';
    case Gzip = 'gz';
}