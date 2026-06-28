<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Manual = 'manual';
    case QrCode = 'qr_code';
    case Rfid = 'rfid';
    case Barcode = 'barcode';
    case FaceRecognition = 'face_recognition';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::QrCode => 'QR Code',
            self::Rfid => 'RFID',
            self::Barcode => 'Barcode',
            self::FaceRecognition => 'Face Recognition',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $method) => [$method->value => $method->label()])
            ->all();
    }
}
