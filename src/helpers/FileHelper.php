<?php
namespace DmitriiKoziuk\yii2FileManager\helpers;

use yii\BaseYii;
use yii\helpers\Inflector;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;

class FileHelper
{
    private $_baseYii;

    private $_uploadFilePath;

    private $thumbnailPath;

    public function __construct(BaseYii $baseYii, string $uploadFilePath, string $thumbnailPath)
    {
        $this->_baseYii = $baseYii;
        $this->_uploadFilePath = $uploadFilePath;
        $this->thumbnailPath = $thumbnailPath;
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

    public function getFileRecordFullPath(FileEntity $file)
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

    public function getFileRecordWebPath(FileEntity $file)
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
        return mb_substr($path, mb_strpos($path, '/web') + 4);
    }

    public function getThumbnailsDirectoryPath(FileEntity $file, int $width, int $height, int $quality): string
    {
        return $this->_baseYii::getAlias($file->location_alias) .
            $this->thumbnailPath .
            DIRECTORY_SEPARATOR .
            $width . 'x' . $height . '-' . $quality .
            DIRECTORY_SEPARATOR .
            $file->entity_name .
            DIRECTORY_SEPARATOR .
            $file->entity_id;
    }

    public function getThumbnailName(FileEntity $file): string
    {
        return $file->id . '.' . $file->extension;
    }

    public function isThumbExist(FileEntity $file, int $width, int $height, int $quality): bool
    {
        $thumb = $this->getThumbnailFullPath($file, $width, $height, $quality);
        if (file_exists($thumb)) {
            return true;
        }
        return false;
    }

    public function getThumbnailFullPath(FileEntity $file, int $width, int $height, int $quality)
    {
        return $this->getThumbnailsDirectoryPath($file, $width, $height, $quality) .
            DIRECTORY_SEPARATOR .
            $this->getThumbnailName($file);
    }

    public function getThumbnailWebPath(FileEntity $file, int $width, int $height, int $quality)
    {
        $fullPath = $this->getThumbnailsDirectoryPath($file, $width, $height, $quality) .
            DIRECTORY_SEPARATOR .
            $this->getThumbnailName($file);
        return mb_substr($fullPath, mb_strpos($fullPath, '/web') + 4);
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
