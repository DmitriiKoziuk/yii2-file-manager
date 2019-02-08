<?php

namespace DmitriiKoziuk\yii2FileManager\controllers\backend;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\base\Module;
use yii\filters\VerbFilter;
use DmitriiKoziuk\yii2FileManager\entities\File;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileForm;
use DmitriiKoziuk\yii2FileManager\data\UploadFileData;
use DmitriiKoziuk\yii2FileManager\data\FileSearchForm;
use DmitriiKoziuk\yii2FileManager\services\FileActionService;
use DmitriiKoziuk\yii2FileManager\services\FileSearchService;
use DmitriiKoziuk\yii2FileManager\helpers\FileWebHelper;

/**
 * FileController implements the CRUD actions for File model.
 */
class FileController extends Controller
{
    /**
     * @var FileActionService
     */
    private $_fileService;

    private $_fileWebHelper;

    public function __construct(
        string $id,
        Module $module,
        FileActionService $fileService,
        FileWebHelper $fileWebHelper,
        array $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->_fileService = $fileService;
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
        return $this->render('create');
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

    /**
     * Finds the File model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return File the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = File::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('dkFileManager', 'The requested page does not exist.'));
    }
}
