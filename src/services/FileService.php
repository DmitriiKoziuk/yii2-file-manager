<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

use Yii;
use yii\base\Component;
use yii\web\UploadedFile;
use yii\imagine\Image;
use yii\helpers\FileHelper;
use DmitriiKoziuk\yii2FileManager\interfaces\FileInterface;
use DmitriiKoziuk\yii2FileManager\events\NewFileAddedEvent;
use DmitriiKoziuk\yii2FileManager\forms\SaveFileToDBForm;
use DmitriiKoziuk\yii2FileManager\forms\GrabFileFromDiskForm;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileFromWebForm;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\entities\GroupEntity;
use DmitriiKoziuk\yii2FileManager\entities\MimeTypeEntity;
use DmitriiKoziuk\yii2FileManager\entities\ImageEntity;
use DmitriiKoziuk\yii2FileManager\repositories\EntityGroupRepository;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\repositories\MimeTypeRepository;
use DmitriiKoziuk\yii2FileManager\repositories\ImageRepository;
use DmitriiKoziuk\yii2FileManager\exceptions\forms\SaveFileToDBFromNotValidException;
use DmitriiKoziuk\yii2FileManager\exceptions\forms\GrabFileFromDiskFormNotValidException;
use DmitriiKoziuk\yii2FileManager\exceptions\forms\UploadFilesFormWebFormNotValidException;
use DmitriiKoziuk\yii2FileManager\exceptions\services\file\CanNotCopyFileException;
use DmitriiKoziuk\yii2FileManager\exceptions\services\file\TryDeleteNotExistFileException;

class FileService extends Component
{
    const EVENT_NEW_FILE_ADDED = 'newFileAdded';

    private FileRepository $fileRepository;
    private ImageRepository $imageRepository;
    private MimeTypeRepository $mimeTypeRepository;
    private EntityGroupRepository $entityGroupRepository;

    public function __construct(
        FileRepository $fileRepository,
        ImageRepository $imageRepository,
        MimeTypeRepository $mimeTypeRepository,
        EntityGroupRepository $entityGroupRepository,
        $config = []
    ) {
        parent::__construct($config);
        $this->fileRepository = $fileRepository;
        $this->imageRepository = $imageRepository;
        $this->mimeTypeRepository = $mimeTypeRepository;
        $this->entityGroupRepository = $entityGroupRepository;
    }

    public function addFileFromDisk(GrabFileFromDiskForm $form): FileEntity {
        if (!$form->validate()) {
            throw new GrabFileFromDiskFormNotValidException();
        }
        if (empty($form->directory)) {
            $form->directory = FileEntity::getWebDirectory($form);
        }
        [
            'file' => $file,
            'name' => $name,
            'realName' => $realName
        ] = $this->diskFileData($form);
        if (!copy($form->file, $file)) {
            throw new CanNotCopyFileException();
        }
        $form = $this->fillSaveFileToDBForm($form, $file, $name, $realName);
        try {
            $fileEntity = $this->saveFileToDB($form);
        } catch (\Throwable $e) {
            unlink($file);
            throw $e;
        }
        return $fileEntity;
    }

    /**
     * @param UploadFileFromWebForm $form
     * @param UploadedFile $uploadedFile
     * @return FileEntity
     * @throws \Throwable
     */
    public function addUploadedFile(UploadFileFromWebForm $form, UploadedFile $uploadedFile): FileEntity
    {
        try {
            if (!$form->validate()) {
                throw new UploadFilesFormWebFormNotValidException();
            }
            if (empty($form->directory)) {
                $form->directory = FileEntity::getWebDirectory($form);
            }
            [
                'file' => $file,
                'name' => $name,
                'realName' => $realName
            ] = $this->uploadFileData($form, $uploadedFile);
            $uploadedFile->saveAs($file);
            $form = $this->fillSaveFileToDBForm($form, $file, $name, $realName);
            return $this->saveFileToDB($form);
        } catch (\Throwable $e) {
            if (isset($file)) {
                unlink($file);
            }
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

    private function saveFileToDB(SaveFileToDBForm $form): FileEntity {
        if (!$form->validate()) {
            throw new SaveFileToDBFromNotValidException();
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $groupEntity = $this->addEntityGroupIfNotExist($form);
            [$mimeType, $mimeSubtype] = explode('/', mime_content_type($form->file));
            $mimeTypeEntity = $this->addMimeTypeIfNotExist($mimeType, $mimeSubtype);
            $size = filesize($form->file);
            $fileEntity = $this->saveFile(
                $groupEntity,
                $mimeTypeEntity,
                $form,
                $size
            );
            if ($fileEntity->isImage()) {
                $this->saveImage($fileEntity);
            }
            $transaction->commit();
            $this->newFileAddedEvent($fileEntity);
            return $fileEntity;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    private function addEntityGroupIfNotExist(FileInterface $file): GroupEntity
    {
        $group = $this->entityGroupRepository->getEntityGroup($file->getModuleName(), $file->getEntityName());
        if (empty($group)) {
            $group = new GroupEntity();
            $group->module_name = $file->getModuleName();
            $group->entity_name = $file->getEntityName();
            $this->entityGroupRepository->save($group);
        }
        return $group;
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
        SaveFileToDBForm $form,
        int $size
    ): FileEntity {
        $entity = new FileEntity();
        $entity->entity_group_id = $groupEntity->id;
        $entity->mime_type_id = $mimeTypeEntity->id;
        $entity->specific_entity_id = $form->specificEntityId;
        $entity->location_alias = $form->locationAlias;
        $entity->directory = $form->directory;
        $entity->name = $form->name;
        $entity->real_name = $form->real_name;
        $entity->size = $size;
        $entity->sort = $this->fileRepository->getFileNextSortIndex($groupEntity->id, $form->specificEntityId);
        $this->fileRepository->save($entity);
        return $entity;
    }

    private function saveImage(FileEntity $fileEntity): ImageEntity {
        $image = $fileEntity->getFileFullPath();
        [$width, $height] = $this->getImageWidthAndHeight($image);
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

    private function diskFileData(GrabFileFromDiskForm $form): array
    {
        $saveTo = FileEntity::getFullPathToFileDirectory($form);
        if(!is_dir($saveTo)) {
            FileHelper::createDirectory($saveTo);
        }
        $fileName = basename($form->file);
        $fileRealName = $fileName;
        $file = $saveTo . '/' . $fileName;
        if (file_exists($file)) {
            ['filename' => $name, 'extension' => $extension] = pathinfo($form->file);
            $fileName = $name . '_' . uniqid() . '.' . $extension;
            $file = $saveTo . '/' . $fileName;
        }
        return ['file' => $file, 'name' => $fileName, 'realName' => $fileRealName];
    }

    private function uploadFileData(
        UploadFileFromWebForm $form,
        UploadedFile $uploadedFile
    ): array {
        $saveTo = FileEntity::getFullPathToFileDirectory($form);
        if(!is_dir($saveTo)) {
            FileHelper::createDirectory($saveTo);
        }
        $fileRealName = $uploadedFile->baseName . '.' . $uploadedFile->extension;
        $fileName = FileEntity::prepareFilename($uploadedFile->baseName) . '.' . $uploadedFile->extension;
        $file = $saveTo .
            DIRECTORY_SEPARATOR .
            $fileName;
        if (file_exists($file)) {
            $fileName = FileEntity::prepareFilename($uploadedFile->baseName) .
                '_' .
                uniqid() .
                '.' .
                $uploadedFile->extension;
            $file = $saveTo .
                DIRECTORY_SEPARATOR .
                $fileName;
        }
        return ['file' => $file, 'name' => $fileName, 'realName' => $fileRealName];
    }

    private function fillSaveFileToDBForm(
        FileInterface $fileI,
        string $file,
        string $name,
        string $realName
    ): SaveFileToDBForm {
        $form = new SaveFileToDBForm();
        $form->file = $file;
        $form->locationAlias = $fileI->getLocationAlias();
        $form->moduleName = $fileI->getModuleName();
        $form->entityName = $fileI->getEntityName();
        $form->specificEntityId = $fileI->getSpecificEntityID();
        $form->directory = $fileI->getDirectory();
        $form->name = $name;
        $form->real_name = $realName;
        return $form;
    }

    private function newFileAddedEvent(FileEntity $fileEntity): void
    {
        $event = new NewFileAddedEvent();
        $event->fileEntity = $fileEntity;
        $this->trigger(self::EVENT_NEW_FILE_ADDED, $event);
    }
}
