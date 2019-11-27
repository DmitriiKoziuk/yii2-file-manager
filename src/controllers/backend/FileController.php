<?php

namespace DmitriiKoziuk\yii2FileManager\controllers\backend;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\base\Module;
use yii\filters\VerbFilter;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileForm;
use DmitriiKoziuk\yii2FileManager\forms\UpdateFileSortForm;
use DmitriiKoziuk\yii2FileManager\data\UploadFileData;
use DmitriiKoziuk\yii2FileManager\data\FileSearchForm;
use DmitriiKoziuk\yii2FileManager\services\FileActionService;
use DmitriiKoziuk\yii2FileManager\services\FileSearchService;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\helpers\FileWebHelper;

/**
 * FileController implements the CRUD actions for File model.
 */
final class FileController extends Controller
{
    /**
     * @var FileActionService
     */
    private $_fileService;

    /**
     * @var FileRepository
     */
    private $fileRepository;

    private $_fileWebHelper;

    public function __construct(
        string $id,
        Module $module,
        FileActionService $fileService,
        FileRepository $fileRepository,
        FileWebHelper $fileWebHelper,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->_fileService = $fileService;
        $this->fileRepository = $fileRepository;
        $this->_fileWebHelper = $fileWebHelper;
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
            'fileWebHelper' => $this->_fileWebHelper,
        ]);
    }

    /**
     * Displays a single File model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @return string
     */
    public function actionCreate()
    {
        $files = $this->fileRepository->getEntityAllFiles(
            FileManagerModule::getId(),
            '1'
        );
        return $this->render('create', [
            'fileWebHelper' => $this->_fileWebHelper,
            'files' => $files,
        ]);
    }

    public function actionUpdate($id)
    {
        //TODO: file update.
    }

    /**
     * @param $id
     * @return Response
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionDelete($id)
    {
        $this->_fileService->deleteFile($id);
        return $this->redirect(['index']);
    }

    /**
     * @return bool
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $uploadFileData = new UploadFileData();
        if ($uploadFileData->load(Yii::$app->request->post()) && $uploadFileData->validate()) {
            $this->_fileService->saveUploadedFiles(new UploadFileForm(), $uploadFileData);
        } else {
            throw new BadRequestHttpException('Bad request.');
        }

        return true;
    }

    public function actionSort()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $updateFileSortForm = new UpdateFileSortForm();
            $updateFileSortForm->fileId = (int) $data['id'];
            $updateFileSortForm->newSort = ++$data['sort'];
            if ($updateFileSortForm->validate()) {
                $this->_fileService->changeFileSort($updateFileSortForm);
            }
        }

        return [
            'status' => 'success',
        ];
    }

    /**
     * Finds the File model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FileEntity the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FileEntity::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('dkFileManager', 'The requested page does not exist.'));
    }
}
