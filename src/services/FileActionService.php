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
    private $_baseYii;

    /**
     * @var string
     */
    private $_uploadFolder;

    /**
     * @var FileHelper
     */
    private $_fileHelper;

    /**
     * @var UploadedFile
     */
    private $_uploadedFile;

    private $_fileRepository;

    public function __construct(
        BaseYii $baseYii,
        string $uploadFolder,
        FileHelper $fileHelper,
        UploadedFile $uploadedFile,
        FileRepository $fileRepository,
        Connection $db = null
    ) {
        parent::__construct($db);
        $this->_baseYii = $baseYii;
        $this->_uploadFolder = $uploadFolder;
        $this->_fileHelper = $fileHelper;
        $this->_uploadedFile = $uploadedFile;
        $this->_fileRepository = $fileRepository;
    }

    /**
     * @param UploadFileForm $form
     * @param UploadFileData $data
     * @return array
     * @throws \Exception
     */
    public function saveUploadedFiles(UploadFileForm $form, UploadFileData $data): array
    {
        $saveLocation = $this->_baseYii::getAlias($data->saveLocationAlias);
        $saveTo = $saveLocation .
            $this->_uploadFolder .
            DIRECTORY_SEPARATOR .
            $data->entityName .
            DIRECTORY_SEPARATOR .
            $data->entityId;
        $this->_fileHelper->createDirectory($saveTo);
        /** @var UploadedFile $uploadedFile */
        $uploadedFiles = $this->_uploadedFile->getInstances($form, 'upload');
        if (empty($uploadedFiles)) {
            throw new \Exception('Cant find uploaded files.');
        }
        foreach ($uploadedFiles as $uploadedFile) {
            if (empty($data->name) || 'null' === $data->name) {
                $fileName = $this->_fileHelper->prepareFilename($uploadedFile->name);
            } else {
                $fileName = $this->_fileHelper->prepareFilename(
                    $data->name .
                    '.' .
                    $this->_fileHelper->defineFileExtension($uploadedFile->name)
                );
            }
            $fullPath = $saveTo . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($fullPath)) {
                $fileName = $this->_fileHelper->defineFileNameWithNumber(
                    $fileName,
                    ($this->_fileHelper->countFilesInDirectory($saveTo) + 1)
                );
                $fullPath = $saveTo . DIRECTORY_SEPARATOR . $fileName;
            }
            if (! $uploadedFile->saveAs($fullPath)) {
                throw new \Exception("Cant save file '{$fileName}' to folder '{$saveTo}'");
            }
            try {
                $file = $this->_saveFileToDB($fullPath, $uploadedFile, $data);
                $this->uploadedFiles[] = $file;
            } catch (\Throwable $e) {
                $this->_fileHelper->deleteFile($fullPath);
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
                $this->_fileHelper->deleteFile($this->_fileHelper->getFileRecordFullPath($fileRecord));
                $this->_decreaseForOneNextFilesSort($fileRecord);
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
    private function _saveFileToDB(string $filePath, UploadedFile $uploadedFile, UploadFileData $data): File
    {
        $this->beginTransaction();
        try {
            $file                 = new File();
            $file->entity_name    = $data->entityName;
            $file->entity_id      = $data->entityId;
            $file->location_alias = $data->saveLocationAlias;
            $file->mime_type      = $this->_fileHelper->getFileMimeType($filePath);
            $file->name           = $this->_fileHelper->defineFileNameWithoutExtension(
                $this->_fileHelper->defineFileNameFromPath($filePath)
            );
            $file->extension      = $this->_fileHelper->defineFileExtension($filePath);
            $file->size           = $uploadedFile->size;
            $file->title          = $uploadedFile->name;
            $file->sort           = File::defineNextSortNumber($data->entityName, $data->entityId);
            $this->_fileRepository->save($file);
            if ($this->_fileHelper->isFileImage($filePath)) {
                $this->_saveImageToDB($file);
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
    private function _saveImageToDB(File $file)
    {
        $imageSource    = new Imagick($this->_fileHelper->getFileRecordFullPath($file));
        $image          = new Image();
        $image->file_id = $file->id;
        $image->width   = $imageSource->getImageWidth();
        $image->height  = $imageSource->getImageHeight();
        $this->_fileRepository->save($image);
        $imageSource->clear();
    }

    private function _decreaseForOneNextFilesSort(File $file)
    {
        File::updateAll(
            [
                'sort' => new Expression('sort - 1'),
            ],
            'entity_name = :entityName AND entity_id = :entityId AND sort > :sort',
            [
                ':entityName' => $file->entity_name,
                ':entityId' => $file->entity_id,
                ':sort' => $file->sort,
            ]
        );
    }
}