<?php

//    Randonneuring.org Website Software
//    Copyright (C) 2026 Chris Nadovich
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    https://randonneuring.org/LICENSE.txt
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <https://www.gnu.org/licenses/>.



namespace App\Libraries;

class WaiverStorage
{
    private string $storageRoot;

    public function __construct(?string $storageRoot = null)
    {
        $this->storageRoot =
            $storageRoot
            ?? WRITEPATH . 'waivers';
    }

    /**
     * Store a document without permitting an existing document
     * to be overwritten.
     *
     * @param string               $documentKey Relative storage path
     * @param string               $contents    Raw document bytes
     * @param string               $contentType MIME content type
     * @param array<string,string> $metadata    Document metadata
     */
    public function storeImmutable(
        string $documentKey,
        string $contents,
        string $contentType,
        array $metadata = []
    ): void {
        if ($contents === '') {
            throw new \InvalidArgumentException(
                'Cannot store an empty waiver document.'
            );
        }

        $documentPath = $this->getDocumentPath(
            $documentKey
        );

        $directory = dirname($documentPath);

        $this->createDirectory($directory);

        /*
         * fopen mode "x" means:
         *
         * - create a new file
         * - fail if the file already exists
         *
         * This prevents accidental replacement of an existing
         * signed waiver.
         */
        $handle = @fopen($documentPath, 'xb');

        if ($handle === false) {
            if (is_file($documentPath)) {
                throw new \RuntimeException(
                    "Waiver document already exists: "
                    . $documentKey
                );
            }

            throw new \RuntimeException(
                "Unable to create waiver document: "
                . $documentKey
            );
        }

        try {
            $this->writeAll(
                $handle,
                $contents,
                $documentKey
            );

            if (!fflush($handle)) {
                throw new \RuntimeException(
                    "Unable to flush waiver document: "
                    . $documentKey
                );
            }
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($documentPath);

            throw $e;
        }

        fclose($handle);

        /*
         * Make the completed document read-only.
         *
         * This is useful protection against accidental changes,
         * though filesystem permissions are not equivalent to
         * S3 Object Lock or true write-once storage.
         */
        @chmod($documentPath, 0440);

        $metadataRecord = array_merge(
            $metadata,
            [
                'document_key' => $documentKey,
                'content_type' => $contentType,
                'size_bytes' => strlen($contents),
                'sha256' => hash('sha256', $contents),
                'stored_at' => gmdate('c'),
            ]
        );

        $this->storeMetadata(
            $documentPath,
            $metadataRecord
        );
    }

    /**
     * Retrieve the raw document bytes.
     */
    public function retrieve(
        string $documentKey
    ): string {
        $documentPath = $this->getDocumentPath(
            $documentKey
        );

        if (!is_file($documentPath)) {
            throw new \RuntimeException(
                "Waiver document does not exist: "
                . $documentKey
            );
        }

        $contents = @file_get_contents($documentPath);

        if ($contents === false) {
            throw new \RuntimeException(
                "Unable to read waiver document: "
                . $documentKey
            );
        }

        return $contents;
    }

    public function exists(
        string $documentKey
    ): bool {
        return is_file(
            $this->getDocumentPath($documentKey)
        );
    }

    /**
     * Turn a document key into a safe path beneath storageRoot.
     */
    private function getDocumentPath(
        string $documentKey
    ): string {
        $documentKey = trim(
            str_replace('\\', '/', $documentKey),
            '/'
        );

        if ($documentKey === '') {
            throw new \InvalidArgumentException(
                'Document key cannot be empty.'
            );
        }

        $parts = explode('/', $documentKey);

        foreach ($parts as $part) {
            if (
                $part === ''
                || $part === '.'
                || $part === '..'
                || !preg_match(
                    '/\A[A-Za-z0-9._-]+\z/',
                    $part
                )
            ) {
                throw new \InvalidArgumentException(
                    'Invalid waiver document key: '
                    . $documentKey
                );
            }
        }

        return rtrim(
            $this->storageRoot,
            DIRECTORY_SEPARATOR
        )
            . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function createDirectory(
        string $directory
    ): void {
        if (is_dir($directory)) {
            return;
        }

        if (
            !@mkdir(
                $directory,
                0770,
                true
            )
            && !is_dir($directory)
        ) {
            throw new \RuntimeException(
                'Unable to create waiver storage directory: '
                . $directory
            );
        }
    }

    /**
     * @param resource $handle
     */
    private function writeAll(
        $handle,
        string $contents,
        string $documentKey
    ): void {
        $length = strlen($contents);
        $offset = 0;

        while ($offset < $length) {
            $written = fwrite(
                $handle,
                substr($contents, $offset)
            );

            if ($written === false || $written === 0) {
                throw new \RuntimeException(
                    "Unable to write waiver document: "
                    . $documentKey
                );
            }

            $offset += $written;
        }
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function storeMetadata(
        string $documentPath,
        array $metadata
    ): void {
        $metadataPath =
            $documentPath . '.metadata.json';

        $json = json_encode(
            $metadata,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );

        /*
         * Like the PDF, the metadata file may not overwrite
         * an existing file.
         */
        $handle = @fopen($metadataPath, 'xb');

        if ($handle === false) {
            throw new \RuntimeException(
                'Unable to create waiver metadata file.'
            );
        }

        try {
            $this->writeAll(
                $handle,
                $json . PHP_EOL,
                basename($metadataPath)
            );

            if (!fflush($handle)) {
                throw new \RuntimeException(
                    'Unable to flush waiver metadata file.'
                );
            }
        } catch (\Throwable $e) {
            fclose($handle);
            @unlink($metadataPath);

            throw $e;
        }

        fclose($handle);

        @chmod($metadataPath, 0440);
    }
}