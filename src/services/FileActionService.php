<?php
namespace DmitriiKoziuk\yii2FileManager\services;

use Imagick;
use yii\BaseYii;
use yii\db\Connection;
use yii\db\Expression;
use yii\web\UploadedFile;
use DmitriiKoziuk\yii2Base\services\DBActionService;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileForm;
use DmitriiKoziuk\yii2FileManager\forms\UpdateFileSortForm;
use DmitriiKoziuk\yii2FileManager\data\UploadFileData;
use DmitriiKoziuk\yii2FileManager\entities\File;
use DmitriiKoziuk\yii2FileManager\entities\Image;
use DmitriiKoziuk\yii2FileManager\exceptions\FileNotFoundException;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;

class FileActionService extends DBActionService
{
    /**
     * @var array
     */
    private $uploadedFiles = [];

    /**
     * @var BaseYii
     */
    private $baseYii;

    /**
     * @var string
     */
    private $uploadFolder;

    /**
     * @var FileHelper
     */
    private $fileHelper;

    /**
     * @var UploadedFile
     */
    private $uploadedFile;

    private $fileRepository;

    public function __construct(
        BaseYii $baseYii,
        string $uploadFolder,
        FileHelper $fileHelper,
        UploadedFile $uploadedFile,
        FileRepository $fileRepository,
        Connection $db = null
    ) {
        parent::__construct($db);
        $this->baseYii = $baseYii;
        $this->uploadFolder = $uploadFolder;
        $this->fileHelper = $fileHelper;
        $this->uploadedFile = $uploadedFile;
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param UploadFileForm $form
     * @param UploadFileData $data
     * @return array
     * @throws \Exception
     */
    public function saveUploadedFiles(UploadFileForm $form, UploadFileData $data): array
    {
        $saveLocation = $this->baseYii::getAlias($data->saveLocationAlias);
        $saveTo = $saveLocation .
            $this->uploadFolder .
            DIRECTORY_SEPARATOR .
            $data->entityName .
            DIRECTORY_SEPARATOR .
            $data->entityId;
        $this->fileHelper->createDirectory($saveTo);
        /** @var UploadedFile $uploadedFile */
        $uploadedFiles = $this->uploadedFile->getInstances($form, 'upload');
        if (empty($uploadedFiles)) {
            throw new \Exception('Cant find uploaded files.');
        }
        foreach ($uploadedFiles as $uploadedFile) {
            if (empty($data->name) || 'null' === $data->name) {
                $fileName = $this->fileHelper->prepareFilename($uploadedFile->name);
            } else {
                $fileName = $this->fileHelper->prepareFilename(
                    $data->name .
                    '.' .
                    $this->fileHelper->defineFileExtension($uploadedFile->name)
                );
            }
            $fullPath = $saveTo . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($fullPath)) {
                $fileName = $this->fileHelper->defineFileNameWithNumber(
                    $fileName,
                    ($this->fileHelper->countFilesInDirectory($saveTo) + 1)
                );
                $fullPath = $saveTo . DIRECTORY_SEPARATOR . $fileName;
            }
            if (! $uploadedFile->saveAs($fullPath)) {
                throw new \Exception("Cant save file '{$fileName}' to folder '{$saveTo}'");
            }
            try {
                $file = $this->saveFileToDB($fullPath, $uploadedFile, $data);
                $this->uploadedFiles[] = $file;
            } catch (\Throwable $e) {
                $this->fileHelper->deleteFile($fullPath);
                break;
            }
        }

        return $this->uploadedFiles;
    }

    /**
     * @param $id
     * @throws FileNotFoundException
     * @throws \DmitriiKoziuk\yii2Base\exceptions\ExternalComponentException
     * @throws \Throwable
     */
    public function deleteFile($id): void
    {
        /** @var File $fileRecord */
        $fileRecord = File::find()
            ->with(['image'])
            ->where(['id' => new Expression(':id')], [':id' => $id])
            ->one();
        if (empty($fileRecord)) {
            throw new FileNotFoundException("File with id '{$id}' not found.");
        } else {
            $this->beginTransaction();
            try {
                if (! empty($fileRecord->image)) {
                    $fileRecord->image->delete();
                }
                $fileRecord->delete();
                $this->fileHelper->deleteFile($this->fileHelper->getFileRecordFullPath($fileRecord));
                $this->fileRepository->decreaseFileSortByOne($fileRecord->entity_name, $fileRecord->entity_id, $fileRecord->sort);
                $this->commitTransaction();
            } catch (\Throwable $e) {
                $this->rollbackTransaction();
                throw $e;
            }
        }
    }

    /**
     * @param string $filePath
     * @param UploadedFile $uploadedFile
     * @param UploadFileData $data
     * @return File
     * @throws \DmitriiKoziuk\yii2Base\exceptions\ExternalComponentException
     * @throws \Throwable
     */
    private function saveFileToDB(string $filePath, UploadedFile $uploadedFile, UploadFileData $data): File
    {
        $this->beginTransaction();
        try {
            $file                 = new File();
            $file->entity_name    = $data->entityName;
            $file->entity_id      = $data->entityId;
            $file->location_alias = $data->saveLocationAlias;
            $file->mime_type      = $this->fileHelper->getFileMimeType($filePath);
            $file->name           = $this->fileHelper->defineFileNameWithoutExtension(
                $this->fileHelper->defineFileNameFromPath($filePath)
            );
            $file->extension      = $this->fileHelper->defineFileExtension($filePath);
            $file->size           = $uploadedFile->size;
            $file->title          = $uploadedFile->name;
            $file->sort           = File::defineNextSortNumber($data->entityName, $data->entityId);
            $this->fileRepository->save($file);
            if ($this->fileHelper->isFileImage($filePath)) {
                $this->saveImageToDB($file);
            }
            $this->commitTransaction();
            return $file;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param File $file
     * @throws \DmitriiKoziuk\yii2Base\exceptions\EntityNotValidException
     * @throws \DmitriiKoziuk\yii2Base\exceptions\EntitySaveException
     * @throws \ImagickException
     */
    private function saveImageToDB(File $file)
    {
        $imageSource    = new Imagick($this->fileHelper->getFileRecordFullPath($file));
        $image          = new Image();
        $image->file_id = $file->id;
        $image->width   = $imageSource->getImageWidth();
        $image->height  = $imageSource->getImageHeight();
        $this->fileRepository->save($image);
        $imageSource->clear();
    }

    /**
     * @param UpdateFileSortForm $form
     * @throws \DmitriiKoziuk\yii2Base\exceptions\ExternalComponentException
     */
    public function changeFileSort(UpdateFileSortForm $form)
    {
        try {
            $this->beginTransaction();
            $fileEntity = $this->fileRepository->getFileById($form->fileId);
            $this->fileRepository->moveFileToEnd($fileEntity);
            $this->fileRepository->increaseFileSortByOne($fileEntity->entity_name, $fileEntity->entity_id, $form->newSort);
            $fileEntity->sort = $form->newSort;
            $this->fileRepository->save($fileEntity);
            $this->commitTransaction();;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
