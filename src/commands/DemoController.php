<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\commands;

use DmitriiKoziuk\yii2FileManager\forms\GrabFileFromDiskForm;
use Yii;
use yii\helpers\Console;
use yii\console\Controller;
use DmitriiKoziuk\yii2FileManager\forms\UploadFileFromWebForm;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\services\FileService;

class DemoController extends Controller
{
    const IMAGES_DIR = __DIR__ . '/data/demo/images';
    const FILES_DIR = __DIR__ . '/data/demo/files';

    private FileService $fileService;

    public function __construct(
        $id,
        $module,
        FileService $fileService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->fileService = $fileService;
        Yii::$app->getLog()->targets = [];
        Yii::$app->getLog()->flushInterval = 1;
    }

    public function actionIndex()
    {
        $this->stdout("Hello\n", Console::FG_BLUE);
        return 0;
    }

    public function actionFillImages($quantity = 100)
    {
        $this->stdout("Start\n", Console::FG_BLUE);
        $imgGenerator = $this->imageGenerator();
        for ($i = 0; $i < $quantity; $i++) {
            $img = $imgGenerator->current();
            $imgGenerator->next();
            $form = $this->createForm($img, ($i + 1));
            $this->fileService->addFileFromDisk($form);
            if (0 !== $i && 0 === ($i % 100)) {
                $this->stdout("{$i} of {$quantity}\n");
            }
        }
        $this->stdout("Done\n", Console::FG_GREEN);
    }

    public function actionFillFiles($quantity = 100)
    {
        $this->stdout("Start\n", Console::FG_BLUE);
        $imgGenerator = $this->fileGenerator();
        for ($i = 0; $i < $quantity; $i++) {
            $file = $imgGenerator->current();
            $imgGenerator->next();
            $form = $this->createForm($file, ($i + 1));
            $this->fileService->addFileFromDisk($form);
            if (0 !== $i && 0 === ($i % 100)) {
                $this->stdout("{$i} of {$quantity}\n");
            }
        }
        $this->stdout("Done\n", Console::FG_GREEN);
    }

    private function imageGenerator()
    {
        $images = $this->getFilesFromDirectory(self::IMAGES_DIR);
        if (empty($images)) {
            return null;
        }
        while (true) {
            $image = current($images);
            if (false === next($images)) {
                reset($images);
            }
            yield self::IMAGES_DIR . '/' . $image;
        }
    }

    private function fileGenerator()
    {
        $files = $this->getFilesFromDirectory(self::FILES_DIR);
        if (empty($files)) {
            return null;
        }
        while (true) {
            $file = current($files);
            if (false === next($files)) {
                reset($files);
            }
            yield self::FILES_DIR . '/' . $file;
        }
    }

    private function getFilesFromDirectory(string $dir)
    {
        return array_filter(scandir($dir), function ($file) {
            return $file !== '.' && $file !== '..';
        });
    }

    private function createForm(string $file, int $specifyEntityId = 1, string $entityName = 'files')
    {
        $form = new GrabFileFromDiskForm();
        $form->file = $file;
        $form->locationAlias = FileEntity::FRONTEND_LOCATION_ALIAS;
        $form->moduleName = 'demo';
        $form->entityName = $entityName;
        $form->specificEntityId = $specifyEntityId;
        return $form;
    }
}
