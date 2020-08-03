<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

use Exception;
use Imagine\Image\Box;
use yii\imagine\Image;
use yii\helpers\FileHelper;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;

class ThumbnailService
{
    private FileRepository $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param int $fileId
     * @param int $width
     * @param int $height
     * @param int $quality
     * @throws Exception
     */
    public function thumbnail(int $fileId, int $width, int $height, int $quality = 65): void
    {
        /** @var FileEntity|null $fileEntity */
        $fileEntity = $this->fileRepository->getFileById($fileId);
        if (empty($fileEntity)) {
            throw new Exception("File with id '{$fileId}' no exist.");
        }
        if (! $fileEntity->isImage()) {
            throw new Exception("File with id '{$fileId}' not image.");
        }
        $originalImage = $fileEntity->getFileFullPath();
        $thumbnailDirectory = $fileEntity->getThumbnailDirectoryFullPath($width, $height, $quality);
        FileHelper::createDirectory($thumbnailDirectory);
        if (!$fileEntity->isThumbnailExist($width, $height, $quality)) {
            $thumbnailImageFullPath = $fileEntity->getThumbnailFullPath($width, $height, $quality);
            Image::getImagine()
                ->open($originalImage)
                ->thumbnail(new Box($width, $height))
                ->save($thumbnailImageFullPath, ['quality' => $quality]);
        }
    }
}
