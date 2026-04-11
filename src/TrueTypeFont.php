<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips;

use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\File;

class TrueTypeFont extends File
{
    /**
     * {@inheritdoc}
     *
     * @see Intervention\Image\Interfaces\FileInterface::fromPath()
     */
    public static function fromPath(string $path): self
    {
        return new self(parent::fromPath($path)->toStream());
    }

    /**
     * Return family name of current font
     *
     * @throws DriverException
     */
    public function familyName(): string
    {
        return $this->queryNameTable(1);
    }

    /**
     * Query name table of current font file
     *
     * @throws DriverException
     */
    private function queryNameTable(int $id): string
    {
        rewind($this->stream);

        $tableOffset = $this->tableOffset('name');
        fseek($this->stream, $tableOffset);

        $header = fread($this->stream, 6);
        $recordCount = unpack('n', substr($header, 2, 2))[1];
        $stringStorageOffset = unpack('n', substr($header, 4, 2))[1];

        for ($i = 0; $i < $recordCount; $i++) {
            $record = fread($this->stream, 12);

            $platformID = unpack('n', substr($record, 0, 2))[1];
            $nameID = unpack('n', substr($record, 6, 2))[1];
            $stringLength = unpack('n', substr($record, 8, 2))[1];
            $stringOffset = unpack('n', substr($record, 10, 2))[1];

            if ($nameID === $id) {
                $currentPos = ftell($this->stream);
                fseek($this->stream, $tableOffset + $stringStorageOffset + $stringOffset);
                $value = fread($this->stream, $stringLength);
                fseek($this->stream, $currentPos);

                if ($platformID === 0 || $platformID === 3) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-16BE');
                }

                return $value;
            }
        }

        throw new DriverException('Unable to find id ' . $id . ' in name table');
    }

    /**
     * Return table offset of given table tag
     *
     * @throws DriverException
     */
    private function tableOffset(string $tableTag): int
    {
        rewind($this->stream);

        $header = fread($this->stream, 12);
        $tableCount = unpack('n', substr($header, 4, 2))[1];
        fseek($this->stream, 12);

        $offsets = [];
        for ($i = 0; $i < $tableCount; $i++) {
            $record = fread($this->stream, 16);
            $offsets[substr($record, 0, 4)] = unpack('N', substr($record, 8, 4))[1];
        }
        if (!array_key_exists($tableTag, $offsets)) {
            throw new DriverException('Unable to find offset for table ' . $tableTag);
        }

        return $offsets[$tableTag];
    }
}
