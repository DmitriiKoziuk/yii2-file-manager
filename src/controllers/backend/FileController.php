<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\controllers\backend;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;
use yii\base\Module;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use DmitriiKoziuk\yii2FileManager\forms\FileUploadForm;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\data\FileSearchForm;
use DmitriiKoziuk\yii2FileManager\services\FileService;
use DmitriiKoziuk\yii2FileManager\services\FileSearchService;
use DmitriiKoziuk\yii2FileManager\exceptions\forms\FileUploadFormNotValidException;

/**
 * FileController implements the CRUD actions for File model.
 */
final class FileController extends Controller
{
    private FileService $fileService;

    public function __construct(
        string $id,
        Module $module,
        FileService $fileService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->fileService = $fileService;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all File models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchForm = new FileSearchForm();
        $searchService = new FileSearchService();
        $dataProvider = $searchService->searchBy($searchForm);

        return $this->render('index', [
            'searchModel' => $searchForm,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single File model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => '',
        ]);
    }

    /**
     * @return string
     */
    public function actionUpload()
    {
        if (Yii::$app->request->isPost) {
            try {
                $fileUploadForm = new FileUploadForm();
                if (
                    ! $fileUploadForm->load(Yii::$app->request->post()) ||
                    ! $fileUploadForm->validate()
                ) {
                    throw new FileUploadFormNotValidException();
                }
                $savedFilesOnDisk = $this->saveUploadedFilesOnDisk(
                    FileUploadForm::getName(),
                    $fileUploadForm->locationAlias,
                    $fileUploadForm->moduleName,
                    $fileUploadForm->entityName,
                    $fileUploadForm->specificEntityId
                );
                $savedFilesToDb = [];
                foreach ($savedFilesOnDisk as $file) {
                    $savedFilesToDb[] = $this->fileService->saveFileToDB(
                        $file['file'],
                        $file['realName'],
                        $fileUploadForm
                    );
                }
                $response['files'] = [];
                foreach ($savedFilesToDb as $file) {
                    $response['files'][] = [
                        'name'         => $file->name,
                        'size'         => $file->size,
                        'url'          => $file->getUrl(),
                        'thumbnailUrl' => $file->getUrl(),
                        'deleteUrl'    => Url::to(['delete', 'id' => $file->id]),
                        'downloadUrl'  => Url::to(['download', 'id' => $file->id]),
                        'deleteType'   => 'POST',
                    ];
                }
                return true;
            } catch (\Throwable $e) {
                Yii::error($e);
                if (isset($savedFilesOnDisk) && ! empty($savedFilesOnDisk)) {
                    foreach ($savedFilesOnDisk as $savedFile) {
                        unlink($savedFile['file']);
                    }
                }
                throw new ServerErrorHttpException();
            }
        }
        return $this->render('upload', [
        ]);
    }

    public function actionUpdate($id)
    {
        //TODO: file update.
    }

    /**
     * @param $id
     * @return Response
     */
    public function actionDelete($id)
    {
        return $this->redirect(['index']);
    }

    public function actionSort()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'status' => 'success',
        ];
    }

    private function saveUploadedFilesOnDisk(
        string $filesArrayName,
        string $location,
        string $moduleName,
        string $entityName,
        int $specificEntityID
    ) {
        try {
            $savedFiles = [];
            $filePath = FileEntity::getUploadFileFolderFullPath($location, $moduleName, $entityName, $specificEntityID);
            if(!is_dir($filePath)) {
                FileHelper::createDirectory($filePath);
            }

            $uploadedFiles = UploadedFile::getInstancesByName($filesArrayName);
            foreach ($uploadedFiles as $uploadedFile) {
                $file = $filePath . '/' . FileEntity::prepareFilename($uploadedFile->baseName) . '.' . $uploadedFile->extension;
                $uploadedFile->saveAs($file);
                $savedFiles[] = [
                    'file' => $file,
                    'realName' => "{$uploadedFile->baseName}.{$uploadedFile->extension}",
                ];
            }
            return $savedFiles;
        } catch (\Throwable $e) {
            if (isset($savedFiles) && ! empty($savedFiles)) {
                foreach ($savedFiles as $savedFile) {
                    unlink($savedFile['file']);
                }
            }
            throw $e;
        }
    }
}
