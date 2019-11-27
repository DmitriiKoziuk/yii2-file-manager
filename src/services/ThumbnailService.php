<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

use Exception;
use Imagine\Image\Box;
use yii\imagine\Image;
use DmitriiKoziuk\yii2FileManager\entities\File;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;

class ThumbnailService
{
    /**
     * @var FileActionService
     */
    private $fileActionService;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    /**
     * @var FileHelper
     */
    private $fileHelper;

    public function __construct(
        FileActionService $fileActionService,
        FileRepository $fileRepository,
        FileHelper $fileHelper
    ) {
        $this->fileActionService = $fileActionService;
        $this->fileRepository = $fileRepository;
        $this->fileHelper = $fileHelper;
    }

    /**
     * @param int $fileId
     * @param int $width
     * @param int $height
     * @param int $quality
     * @throws Exception
     */
    public function thumbnail(int $fileId, int $width, int $height, int $quality = 65)
    {
        /** @var File|null $fileEntity */
        $fileEntity = $this->fileRepository->getFileById($fileId);
        if (empty($fileEntity)) {
            throw new Exception("File with id '{$fileId}' no exist.");
        }
        if (empty($fileEntity->image)) {
            throw new Exception("File with id '{$fileId}' not image.");
        }
        $originalImage = $this->fileHelper->getFileRecordFullPath($fileEntity);
        $thumbnailDirectory = $this->fileHelper->getThumbnailsDirectoryPath($fileEntity, $width, $height, $quality);
        $this->fileHelper->createDirectory($thumbnailDirectory);
        if (! $this->fileHelper->isThumbExist($fileEntity, $width, $height, $quality)) {
            $thumbnailImage = $this->fileHelper->getThumbnailFullPath($fileEntity, $width, $height, $quality);
            Image::getImagine()
                ->open($originalImage)
                ->thumbnail(new Box($width, $height))
                ->save($thumbnailImage, ['quality' => $quality]);
        }
    }
}
