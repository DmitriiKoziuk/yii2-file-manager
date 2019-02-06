<?php
namespace DmitriiKoziuk\yii2FileManager\helpers;

use yii\BaseYii;
use yii\helpers\Inflector;
use DmitriiKoziuk\yii2FileManager\entities\File;

class FileHelper
{
    private $_baseYii;

    private $_uploadFilePath;

    public function __construct(BaseYii $baseYii, string $uploadFilePath)
    {
        $this->_baseYii = $baseYii;
        $this->_uploadFilePath = $uploadFilePath;
    }

    /**
     * @param string $path
     * @throws \Exception
     */
    public function createDirectory(string $path): void
    {
        if (! file_exists($path)) {
            if (! mkdir($path, 0755, true)) {
                throw new \Exception("Cant create directory '{$path}'");
            }
        }
    }

    /**
     * @param string $path
     * @throws \Exception
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (file_exists($path)) {
            if (! unlink($path)) {
                throw new \Exception("Cant delete file '{$path}'");
            }
        }
        return true;
    }

    /**
     * Transliterate file name, to lower, left only characters a-z 0-9 - .
     * @param string $fileName
     * @return string
     */
    public function prepareFilename(string $fileName): string
    {
        $fileName = trim($fileName);
        $fileName = Inflector::transliterate($fileName);
        $fileName = mb_strtolower($fileName);
        $fileName = preg_replace("/[^a-z0-9.\s]/","-", $fileName);
        $fileName = preg_replace('/\s{1,}/', '-', $fileName);
        $fileName = preg_replace('/[-]{2,}/', '-', $fileName);
        return $fileName;
    }

    /**
     * @param string $file
     * @param string $newName
     * @return bool
     * @throws \Exception
     */
    public function renameFile(string $file, string $newName): bool
    {
        if (! rename(
            $file,
            dirname($file) . '/' . $this->prepareFilename($newName))
        ) {
            throw new \Exception("Cant rename file");
        }
        return true;
    }

    public function defineFileNameFromPath(string $path): string
    {
        $lastSlash = strrpos($path, '/');
        $fileName  = substr($path, ++$lastSlash);
        return $fileName;
    }

    public function defineFileNameWithoutExtension(string $fileName): string
    {
        $fileName = substr(
            $fileName,
            0,
            ((mb_strlen($fileName) - mb_strlen($this->defineFileExtension($fileName))) - 1)
        );
        return $fileName;
    }

    public function defineFileExtension(string $fileName): string
    {
        $dotLastPosition = strrpos($fileName, '.');
        $extension       = substr($fileName, ++$dotLastPosition);
        return $extension;
    }

    public function defineFileNameWithNumber(string $fileName, string $number): string
    {
        $fileName = $this->defineFileNameWithoutExtension($fileName) .
            '-' .
            $number .
            '.' .
            $this->defineFileExtension($fileName);
        return $fileName;
    }

    public function defineFileWebPath(string $filePath, string $location): string
    {
        $webPath = str_replace(\Yii::getAlias($location) . '/web', '', $filePath);
        return $webPath;
    }

    public function countFilesInDirectory(string $path): int
    {
        $fi = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
        return iterator_count($fi);
    }

    public function getFileRecordFullPath(File $file)
    {
        return $this->_baseYii::getAlias($file->location_alias) .
            $this->_uploadFilePath .
            DIRECTORY_SEPARATOR .
            $file->entity_name .
            DIRECTORY_SEPARATOR .
            $file->entity_id .
            DIRECTORY_SEPARATOR .
            $file->name .
            '.' .
            $file->extension;
    }

    public function getFileRecordWebPath(File $file)
    {
        $path = $this->_uploadFilePath .
            DIRECTORY_SEPARATOR .
            $file->entity_name .
            DIRECTORY_SEPARATOR .
            $file->entity_id .
            DIRECTORY_SEPARATOR .
            $file->name .
            '.' .
            $file->extension;
        return str_replace('/web', '', $path);
    }

    public function getFileMimeType(string $file)
    {
        return mime_content_type($file);
    }

    public function isFileImage(string $file)
    {
        return preg_match('/^image\/.*$/', $this->getFileMimeType($file));
    }
}