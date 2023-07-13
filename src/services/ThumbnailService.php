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
     * @param int $fileID
     * @param int $width
     * @param int|null $height
     * @param int $quality
     * @throws Exception
     */
    public function thumbnail(int $fileID, int $width, int $height = null, int $quality = 60): void
    {
        /** @var FileEntity|null $fileEntity */
        $fileEntity = $this->fileRepository->getFileById($fileID);
        if (empty($fileEntity)) {
            throw new Exception("File with id '{$fileID}' no exist.");
        }
        if (! $fileEntity->isImage()) {
            throw new Exception("File with id '{$fileID}' not image.");
        }
        if ($fileEntity->image->width < $width) {
            return;
        }
        if (!$fileEntity->isThumbnailExist($width, $height, $quality)) {
            $originalImage      = $fileEntity->getFileFullPath2();
            $thumbnailDirectory = $fileEntity->getThumbnailDirectoryFullPath($width, $height, $quality);
            FileHelper::createDirectory($thumbnailDirectory);
            $thumbnailImageFullPath = $fileEntity->getThumbnailFullPath($width, $height, $quality);

            if (null === $height) {
                $height = $fileEntity->image->height;
            }

            if (false === file_exists($originalImage)) {
                return;
            }

            Image::getImagine()
                ->open($originalImage)
                ->thumbnail(new Box($width, $height))
                ->save($thumbnailImageFullPath, ['quality' => $quality]);
        }
    }
}
