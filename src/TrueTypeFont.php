<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\Exceptions\FontException;
use Intervention\Image\File;

class TrueTypeFont extends File
{
    /**
     * Create object from path in file system
     *
     * @param string $path
     * @throws RuntimeException
     * @return TrueTypeFont
     */
    public static function fromPath(string $path): self
    {
        return new self(fopen($path, 'r'));
    }

    /**
     * Read font family name from current ttf file
     *
     * @throws FontException
     * @return string
     */
    public function familyName(): string
    {
        $fontFamilyName = null;

        $header = fread($this->pointer, 12);
        $tableCount = unpack('n', substr($header, 4, 2))[1];
        fseek($this->pointer, 12);

        $nameTableOffset = null;

        // read all table records
        for ($i = 0; $i < $tableCount; $i++) {
            $record = fread($this->pointer, 16);

            // search for "name" table and its offset
            if (substr($record, 0, 4) === 'name') {
                $nameTableOffset = unpack('N', substr($record, 8, 4))[1];
                break;
            }
        }

        if (is_null($nameTableOffset)) {
            fclose($this->pointer);
            throw new FontException('Unable to find name table in TTF file.');
        }

        // read the "name" table
        fseek($this->pointer, $nameTableOffset);
        $nameTableHeader = fread($this->pointer, 6);
        $nameRecordCount = unpack('n', substr($nameTableHeader, 2, 2))[1];
        $stringStorageOffset = unpack('n', substr($nameTableHeader, 4, 2))[1];

        // read all "name" records
        for ($i = 0; $i < $nameRecordCount; $i++) {
            $nameRecord = fread($this->pointer, 12);
            if (strlen($nameRecord) !== 12) {
                fclose($this->pointer);
                throw new FontException('Invalid name record in TTF file.');
            }

            $platformID = unpack('n', substr($nameRecord, 0, 2))[1];
            $nameID = unpack('n', substr($nameRecord, 6, 2))[1];
            $stringLength = unpack('n', substr($nameRecord, 8, 2))[1];
            $stringOffset = unpack('n', substr($nameRecord, 10, 2))[1];

            // ID 1 is font family name
            if ($nameID === 1) {
                $currentPos = ftell($this->pointer);
                fseek($this->pointer, $nameTableOffset + $stringStorageOffset + $stringOffset);
                $fontFamilyName = fread($this->pointer, $stringLength);
                fseek($this->pointer, $currentPos);

                if ($platformID === 0 || $platformID === 3) {
                    $fontFamilyName = mb_convert_encoding($fontFamilyName, 'UTF-8', 'UTF-16BE');
                }

                break;
            }
        }

        if (is_null($fontFamilyName)) {
            fclose($this->pointer);
            throw new FontException('Unable to read font family name from TTF file.');
        }

        return $fontFamilyName;
    }
}
