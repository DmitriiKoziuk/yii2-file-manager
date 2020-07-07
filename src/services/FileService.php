<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

use Yii;
use yii\imagine\Image;
use DmitriiKoziuk\yii2FileManager\forms\FileUploadForm;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\entities\GroupEntity;
use DmitriiKoziuk\yii2FileManager\entities\MimeTypeEntity;
use DmitriiKoziuk\yii2FileManager\entities\ImageEntity;
use DmitriiKoziuk\yii2FileManager\repositories\EntityGroupRepository;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\repositories\MimeTypeRepository;
use DmitriiKoziuk\yii2FileManager\repositories\ImageRepository;
use DmitriiKoziuk\yii2FileManager\exceptions\forms\FileUploadFormNotValidException;
use DmitriiKoziuk\yii2FileManager\exceptions\services\file\TryDeleteNotExistFileException;

class FileService
{
    private EntityGroupRepository $entityGroupRepository;
    private MimeTypeRepository $mimeTypeRepository;
    private FileRepository $fileRepository;
    private ImageRepository $imageRepository;

    public function __construct(
        EntityGroupRepository $entityGroupRepository,
        MimeTypeRepository $mimeTypeRepository,
        FileRepository $fileRepository,
        ImageRepository $imageRepository
    ) {
        $this->entityGroupRepository = $entityGroupRepository;
        $this->mimeTypeRepository = $mimeTypeRepository;
        $this->fileRepository = $fileRepository;
        $this->imageRepository = $imageRepository;
    }

    public function saveFileToDB(
        string $file,
        string $fileRealName,
        FileUploadForm $fileUploadForm
    ): FileEntity {
        if (! $fileUploadForm->validate()) {
            throw new FileUploadFormNotValidException();
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $GroupEntity = $this->addEntityGroupIfNotExist($fileUploadForm);
            [$mimeType, $mimeSubtype] = explode('/', mime_content_type($file));
            $mimeTypeEntity = $this->addMimeTypeIfNotExist($mimeType, $mimeSubtype);
            $fileName = pathinfo($file)['basename'];
            $size     = filesize($file);
            $fileEntity = $this->saveFile(
                $GroupEntity,
                $mimeTypeEntity,
                $fileUploadForm,
                $fileName,
                $fileRealName,
                $size
            );
            if ($fileEntity->isImage()) {
                $this->saveFileImage($fileEntity, $GroupEntity);
            }
            $transaction->commit();
            return $fileEntity;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function deleteFile(int $fileId): void
    {
        $fileEntity = $this->fileRepository->getFileById($fileId);
        if (empty($fileEntity)) {
            throw new TryDeleteNotExistFileException();
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $this->deleteFileFromDB($fileEntity);
            $this->deleteFileFromDisk($fileEntity);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function addEntityGroupIfNotExist(FileUploadForm $form): GroupEntity
    {
        $entity = $this->entityGroupRepository->getEntityGroup($form->moduleName, $form->entityName);
        if (empty($entity)) {
            $entity = new GroupEntity();
            $entity->module_name = $form->moduleName;
            $entity->entity_name = $form->entityName;
            $this->entityGroupRepository->save($entity);
        }
        return $entity;
    }

    private function addMimeTypeIfNotExist(string $type, string $subtype)
    {
        $entity = $this->mimeTypeRepository->getMimeType($type, $subtype);
        if (empty($entity)) {
            $entity = new MimeTypeEntity();
            $entity->type = $type;
            $entity->subtype = $subtype;
            $this->mimeTypeRepository->save($entity);
        }
        return $entity;
    }

    private function saveFile(
        GroupEntity $groupEntity,
        MimeTypeEntity $mimeTypeEntity,
        FileUploadForm $fileUploadForm,
        string $fileName,
        string $fileRealName,
        int $size
    ): FileEntity {
        $entity = new FileEntity();
        $entity->entity_group_id = $groupEntity->id;
        $entity->mime_type_id = $mimeTypeEntity->id;
        $entity->specific_entity_id = $fileUploadForm->specificEntityId;
        $entity->location_alias = $fileUploadForm->locationAlias;
        $entity->name = $fileName;
        $entity->real_name = $fileRealName;
        $entity->size = $size;
        $entity->sort = $this->fileRepository->getFileNextSortIndex($groupEntity->id, $fileUploadForm->specificEntityId);
        $this->fileRepository->save($entity);
        return $entity;
    }

    private function saveFileImage(
        FileEntity $fileEntity,
        GroupEntity $groupEntity
    ): ImageEntity {
        $uploadFolder = FileEntity::getUploadFileFolderFullPath(
            $fileEntity->location_alias,
            $groupEntity->module_name,
            $groupEntity->entity_name,
            $fileEntity->specific_entity_id
        );
        $fullImagePath = $uploadFolder . '/' . $fileEntity->name;
        [$width, $height] = $this->getImageWidthAndHeight($fullImagePath);
        $entity = new ImageEntity();
        $entity->file_id = $fileEntity->id;
        $entity->width = $width;
        $entity->height = $height;
        if ($width == $height) {
            $entity->orientation = ImageEntity::ORIENTATION_SQUARE;
        } elseif ($width > $height) {
            $entity->orientation = ImageEntity::ORIENTATION_LANDSCAPE;
        } else {
            $entity->orientation = ImageEntity::ORIENTATION_PORTRAIT;
        }
        $this->imageRepository->save($entity);
        return $entity;
    }

    /**
     * @param string $fullPathToImage
     * @return array [Width, Height]
     */
    private function getImageWidthAndHeight(string $fullPathToImage): array
    {
        $image = Image::getImagine()
            ->open($fullPathToImage);
        $size = $image->getSize();
        return [$size->getWidth(), $size->getHeight()];
    }

    private function deleteFileFromDB(FileEntity $fileEntity): void
    {
        if ($fileEntity->isImage()) {
            $this->imageRepository->delete($fileEntity->image);
        }
        $this->fileRepository->delete($fileEntity);
    }

    private function deleteFileFromDisk(FileEntity $fileEntity): void
    {
        $file = $fileEntity->getFileFullPath();
        if (is_file($file)) {
            unlink($fileEntity->getFileFullPath());
        }
    }
}
