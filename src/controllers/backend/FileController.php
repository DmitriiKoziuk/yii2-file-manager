<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\controllers\backend;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;
use yii\base\Module;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileFromWebForm;
use DmitriiKoziuk\yii2FileManager\data\FileSearchForm;
use DmitriiKoziuk\yii2FileManager\services\SettingsService;
use DmitriiKoziuk\yii2FileManager\services\FileService;
use DmitriiKoziuk\yii2FileManager\services\FileSearchService;
use DmitriiKoziuk\yii2FileManager\exceptions\forms\UploadFilesFormWebFormNotValidException;

/**
 * FileController implements the CRUD actions for File model.
 */
final class FileController extends Controller
{
    private FileService $fileService;

    private SettingsService $settings;

    public function __construct(
        string $id,
        Module $module,
        SettingsService $settingsService,
        FileService $fileService,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->settings = $settingsService;
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
            'settings' => $this->settings,
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

    public function actionUpload()
    {
        if (Yii::$app->request->isPost) {
            try {
                $fileUploadForm = new UploadFileFromWebForm();
                if (
                    ! $fileUploadForm->load(Yii::$app->request->post()) ||
                    ! $fileUploadForm->validate()
                ) {
                    throw new UploadFilesFormWebFormNotValidException();
                }
                $uploadedFiles = UploadedFile::getInstancesByName(UploadFileFromWebForm::getName());
                $savedFilesToDb = [];
                foreach ($uploadedFiles as $uploadedFile) {
                    $savedFilesToDb[] = $this->fileService->addUploadedFile($fileUploadForm, $uploadedFile);
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

    public function actionDelete(int $id)
    {
        try {
            $this->fileService->deleteFile($id);
        } catch (\Exception $e) {
            throw $e;
        }
        return $this->redirect(['index']);
    }

    public function actionSort()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'status' => 'success',
        ];
    }
}
