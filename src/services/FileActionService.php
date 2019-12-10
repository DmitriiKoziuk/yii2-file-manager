<?php

namespace DmitriiKoziuk\yii2FileManager\services;

use Imagick;
use yii\BaseYii;
use yii\db\Connection;
use yii\db\Expression;
use yii\web\UploadedFile;
use DmitriiKoziuk\yii2Base\traits\ModelValidatorTrait;
use DmitriiKoziuk\yii2Base\services\DBActionService;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileForm;
use DmitriiKoziuk\yii2FileManager\forms\UpdateFileSortForm;
use DmitriiKoziuk\yii2FileManager\forms\FetchFileForm;
use DmitriiKoziuk\yii2FileManager\data\UploadFileData;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\exceptions\FileNotFoundException;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\interfaces\SaveFileInterface;

class FileActionService extends DBActionService
{
    use ModelValidatorTrait;

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
        $fileDirectory = $this->getFileDirectory($data);
        $this->fileHelper->createDirectoryIfNotExist($fileDirectory);
        /** @var UploadedFile $uploadedFile */
        $uploadedFiles = $this->uploadedFile->getInstances($form, 'upload');
        if (empty($uploadedFiles)) {
            throw new \Exception('Cant find uploaded files.');
        }
        foreach ($uploadedFiles as $uploadedFile) {
            if ($data->isRenameFile()) {
                $fileName = $this->fileHelper->prepareFilename(
                    $data->getNewFileName() .
                    '.' .
                    $this->fileHelper->defineFileExtension($uploadedFile->name)
                );
            } else {
                $fileName = $this->fileHelper->prepareFilename($uploadedFile->name);
            }
            $file = $fileDirectory . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($file)) {
                $fileName = $this->fileHelper->defineFileNameWithNumber(
                    $fileName,
                    ($this->fileHelper->countFilesInDirectory($fileDirectory) + 1)
                );
                $file = $fileDirectory . DIRECTORY_SEPARATOR . $fileName;
            }
            if (! $uploadedFile->saveAs($file)) {
                throw new \Exception("Cant save file '{$fileName}' to folder '{$fileDirectory}'");
            }
            try {
                $file = $this->saveFileToDB($file, $data);
                $this->uploadedFiles[] = $file;
            } catch (\Throwable $e) {
                $this->fileHelper->deleteFile($file);
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
        /** @var FileEntity $fileRecord */
        $fileRecord = FileEntity::find()
            ->where(['id' => new Expression(':id')], [':id' => $id])
            ->one();
        if (empty($fileRecord)) {
            throw new FileNotFoundException("File with id '{$id}' not found.");
        } else {
            $this->beginTransaction();
            try {
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
     * @param SaveFileInterface $saveFileToDB
     * @return FileEntity
     * @throws \DmitriiKoziuk\yii2Base\exceptions\ExternalComponentException
     * @throws \Throwable
     */
    private function saveFileToDB(string $filePath, SaveFileInterface $saveFileToDB): FileEntity
    {
        $this->beginTransaction();
        try {
            $file                 = new FileEntity();
            $file->entity_name    = $saveFileToDB->getEntityName();
            $file->entity_id      = $saveFileToDB->getEntityId();
            $file->location_alias = $saveFileToDB->getSaveLocationAlias();
            $file->mime_type      = $this->fileHelper->getFileMimeType($filePath);
            $file->name           = $this->fileHelper->defineFileNameWithoutExtension(
                $this->fileHelper->defineFileNameFromPath($filePath)
            );
            $file->extension      = $this->fileHelper->defineFileExtension($filePath);
            $file->size           = filesize($filePath);
            $file->sort           = $this->fileRepository->defineNextSortNumber(
                $saveFileToDB->getEntityName(),
                $saveFileToDB->getEntityId()
            );
            if ($this->fileHelper->isImage($filePath)) {
                $imageSource  = new Imagick($this->fileHelper->getFileRecordFullPath($file));
                $file->width  = $imageSource->getImageWidth();
                $file->height = $imageSource->getImageHeight();
                $imageSource->clear();
            }
            $this->fileRepository->save($file);
            $this->commitTransaction();
            return $file;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
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

    public function fetchFile(FetchFileForm $fetchFileForm): FileEntity
    {
        $this->validateModels([$fetchFileForm]);
        $FileDirectory = $this->getFileDirectory($fetchFileForm);
        $this->fileHelper->createDirectoryIfNotExist($FileDirectory);
        $fileName = basename($fetchFileForm->source);
        if ($fetchFileForm->isOptimizeFileName()) {
            $fileName = $this->fileHelper->prepareFilename(
                $this->fileHelper->defineFileNameWithoutExtension($fileName)
            ) . '.' . $this->fileHelper->defineFileExtension($fileName);
        }
        if ($fetchFileForm->isRenameFile()) {
            if ($fetchFileForm->isOptimizeFileName()) {
                $fileName = $this->fileHelper->prepareFilename($fetchFileForm->getNewFileName()) .
                    '.' .
                    $this->fileHelper->defineFileExtension($fileName);
            } else {
                $fileName = $fetchFileForm->getNewFileName() . '.' . $this->fileHelper->defineFileExtension($fileName);
            }
        }
        $file = $FileDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($file) && false === $fetchFileForm->overwriteExistFile) {
            $fileName = $this->fileHelper->defineFileNameWithNumber(
                $fileName,
                ($this->fileHelper->countFilesInDirectory($FileDirectory) + 1)
            );
            $file = $FileDirectory . DIRECTORY_SEPARATOR . $fileName;
        }
        try {
            file_put_contents($file, fopen($fetchFileForm->source, 'r'));
            return $this->saveFileToDB($file, $fetchFileForm);
        } catch (\Throwable $e) {
            $this->fileHelper->deleteFile($file);
            throw $e;
        }
    }

    private function getFileDirectory(SaveFileInterface $saveFileToDB): string
    {
        return $this->baseYii::getAlias($saveFileToDB->getSaveLocationAlias()) .
            $this->uploadFolder .
            DIRECTORY_SEPARATOR .
            $saveFileToDB->getEntityName() .
            DIRECTORY_SEPARATOR .
            $saveFileToDB->getEntityId();
    }
}
