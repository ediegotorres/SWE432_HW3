<?php
declare(strict_types=1);

final class JsonUploadReader
{
    public function read(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid JSON file.');
        }
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') {
            throw new InvalidArgumentException('The file must have a .json extension.');
        }
        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false) {
            throw new RuntimeException('The file could not be read.');
        }
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR); // Invalid JSON throws an exception.
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new InvalidArgumentException('The JSON file must contain an array of records.');
        }
        return $decoded;
    }
}
