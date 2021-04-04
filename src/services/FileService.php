<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

use DmitriiKoziuk\yii2FileManager\exceptions\forms\AddFileFormNotValidException;
use DmitriiKoziuk\yii2FileManager\forms\AddFileForm;
use Throwable;
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

    protected FileRepository $fileRepository;
    protected ImageRepository $imageRepository;
    protected MimeTypeRepository $mimeTypeRepository;
    protected EntityGroupRepository $entityGroupRepository;

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

    /**
     * @param AddFileForm $form
     * @return FileEntity
     * @throws AddFileFormNotValidException
     * @throws Throwable
     */
    public function addFile(AddFileForm $form): FileEntity
    {
        if (!$form->validate()) {
            throw new AddFileFormNotValidException();
        }
        $file = $form->file;
        $name = basename($form->file);
        $realName = $name;
        $form = $this->fillFormSaveFileToDB($form, $file, $name, $realName, $form->moduleDirectory);
        try {
            $fileEntity = $this->saveFileToDB($form);
        } catch (Throwable $e) {
            unlink($file);
            throw $e;
        }
        return $fileEntity;
    }

    /**
     * @param GrabFileFromDiskForm $form
     * @return FileEntity
     * @throws CanNotCopyFileException
     * @throws GrabFileFromDiskFormNotValidException
     * @throws SaveFileToDBFromNotValidException
     * @throws Throwable
     */
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
        $form = $this->fillFormSaveFileToDB($form, $file, $name, $realName);
        try {
            $fileEntity = $this->saveFileToDB($form);
        } catch (Throwable $e) {
            unlink($file);
            throw $e;
        }
        return $fileEntity;
    }

    /**
     * @param UploadFileFromWebForm $form
     * @param UploadedFile $uploadedFile
     * @return FileEntity
     * @throws Throwable
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
            $form = $this->fillFormSaveFileToDB($form, $file, $name, $realName);
            return $this->saveFileToDB($form);
        } catch (Throwable $e) {
            if (isset($file)) {
                unlink($file);
            }
            throw $e;
        }
    }

    /**
     * @param int $fileId
     * @throws TryDeleteNotExistFileException
     * @throws Throwable
     */
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
        } catch (Throwable $t) {
            $transaction->rollBack();
            throw $t;
        }
    }

    /**
     * @param SaveFileToDBForm $form
     * @return FileEntity
     * @throws SaveFileToDBFromNotValidException
     * @throws Throwable
     */
    protected function saveFileToDB(SaveFileToDBForm $form): FileEntity {
        if (!$form->validate()) {
            throw new SaveFileToDBFromNotValidException();
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $groupEntity = $this->addEntityGroupIfNotExist($form);
            $mimeTypeEntity = $this->addMimeTypeIfNotExist($form->getMimeType(), $form->getMimeSubtype());
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
        } catch (Throwable $t) {
            $transaction->rollBack();
            throw $t;
        }
    }

    /**
     * @param SaveFileToDBForm $file
     * @return GroupEntity
     * @throws Throwable
     */
    protected function addEntityGroupIfNotExist(SaveFileToDBForm $file): GroupEntity
    {
        $group = $this->entityGroupRepository->getEntityGroup($file->getModuleName(), $file->getEntityName());
        if (empty($group)) {
            $group = new GroupEntity();
            $group->module_name = $file->getModuleName();
            $group->entity_name = $file->getEntityName();
            $group->files_directory = $file->getModuleDirectory();
            $this->entityGroupRepository->save($group);
        }
        return $group;
    }

    /**
     * @param string $type
     * @param string $subtype
     * @return MimeTypeEntity
     * @throws Throwable
     */
    protected function addMimeTypeIfNotExist(string $type, string $subtype): MimeTypeEntity
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

    /**
     * @param GroupEntity $groupEntity
     * @param MimeTypeEntity $mimeTypeEntity
     * @param SaveFileToDBForm $form
     * @param int $size
     * @return FileEntity
     * @throws Throwable
     */
    protected function saveFile(
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

    /**
     * @param FileEntity $fileEntity
     * @return ImageEntity
     * @throws Throwable
     */
    protected function saveImage(FileEntity $fileEntity): ImageEntity {
        $image = $fileEntity->getFileFullPath2();
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
    protected function getImageWidthAndHeight(string $fullPathToImage): array
    {
        $image = Image::getImagine()
            ->open($fullPathToImage);
        $size = $image->getSize();
        return [$size->getWidth(), $size->getHeight()];
    }

    /**
     * @param FileEntity $fileEntity
     * @throws Throwable
     */
    protected function deleteFileFromDB(FileEntity $fileEntity): void
    {
        if ($fileEntity->isImage()) {
            $this->imageRepository->delete($fileEntity->image);
        }
        $this->fileRepository->delete($fileEntity);
    }

    protected function deleteFileFromDisk(FileEntity $fileEntity): void
    {
        $file = $fileEntity->getFileFullPath();
        if (is_file($file)) {
            unlink($fileEntity->getFileFullPath());
        }
    }

    /**
     * @param GrabFileFromDiskForm $form
     * @return array
     * @throws Throwable
     */
    protected function diskFileData(GrabFileFromDiskForm $form): array
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

    /**
     * @param UploadFileFromWebForm $form
     * @param UploadedFile $uploadedFile
     * @return string[]
     * @throws Throwable
     */
    protected function uploadFileData(
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

    protected function fillFormSaveFileToDB(
        FileInterface $fileI,
        string $file,
        string $name,
        string $realName,
        ?string $moduleDirectory = null
    ): SaveFileToDBForm {
        [$mimeType, $mimeSubtype] = explode('/', mime_content_type($file));
        $form = new SaveFileToDBForm();
        $form->file = $file;
        $form->locationAlias = $fileI->getLocationAlias();
        $form->moduleName = $fileI->getModuleName();
        $form->entityName = $fileI->getEntityName();
        $form->moduleDirectory = $moduleDirectory;
        $form->specificEntityId = $fileI->getSpecificEntityID();
        $form->directory = $fileI->getDirectory();
        $form->name = $name;
        $form->real_name = $realName;
        $form->mimeType = $mimeType;
        $form->mimeSubtype = $mimeSubtype;
        return $form;
    }

    protected function newFileAddedEvent(FileEntity $fileEntity): void
    {
        $event = new NewFileAddedEvent();
        $event->fileEntity = $fileEntity;
        $this->trigger(self::EVENT_NEW_FILE_ADDED, $event);
    }
}
