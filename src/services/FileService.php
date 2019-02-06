<?php
namespace DmitriiKoziuk\yii2FileManager\services;

use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;

class FileService
{
    private $_fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->_fileRepository = $fileRepository;
    }

    public function getAllFiles(string $entityName, string $entityId): array
    {
        return $this->_fileRepository->getEntityAllFiles($entityName, $entityId);
    }

    public function getImages(string $entityName, string $entityId): array
    {
        return $this->_fileRepository->getEntityImages($entityName, $entityId);
    }
}