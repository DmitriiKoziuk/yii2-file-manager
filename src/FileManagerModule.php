<?php
namespace DmitriiKoziuk\yii2FileManager;

use yii\BaseYii;
use yii\di\Container;
use yii\web\UploadedFile;
use yii\web\Application as WebApp;
use yii\base\Application as BaseApp;
use yii\console\Application as ConsoleApp;
use yii\queue\cli\Queue;
use DmitriiKoziuk\yii2ModuleManager\interfaces\ModuleInterface;
use DmitriiKoziuk\yii2ModuleManager\ModuleManager;
use DmitriiKoziuk\yii2ConfigManager\ConfigManagerModule;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\services\FileActionService;
use DmitriiKoziuk\yii2FileManager\services\ThumbnailService;
use DmitriiKoziuk\yii2FileManager\helpers\FileWebHelper;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;

final class FileManagerModule extends \yii\base\Module implements ModuleInterface
{
    const ID = 'dk-file-manager';

    const TRANSLATE = self::ID;

    /**
     * @var Container
     */
    public $diContainer;

    /**
     * @var Queue
     */
    public $queue;

    /**
     * Overwrite this param if you backend app id is different from default.
     * @var string
     */
    public $backendAppId;

    /**
     * Domain name with protocol and without end slash.
     * Need for display image preview that load in @frontend location.
     * @var string
     */
    public $frontendDomainName;

    /**
     * @var string
     */
    public $uploadFilePath;

    public $imageThumbPath;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function init()
    {
        /** @var BaseApp $app */
        $app = $this->module;
        $this->_initLocalProperties($app);
        $this->_registerTranslation($app);
        $this->_registerClassesToDIContainer($app);
    }

    public static function getId(): string
    {
        return self::ID;
    }

    public function getBackendMenuItems(): array
    {
        return ['label' => 'File manager', 'url' => ['/' . self::ID . '/file/index']];
    }

    public static function requireOtherModulesToBeActive(): array
    {
        return [
            ModuleManager::class,
            ConfigManagerModule::class,
        ];
    }

    /**
     * @param BaseApp $app
     * @throws \InvalidArgumentException
     */
    private function _initLocalProperties(BaseApp $app)
    {
        if ($app instanceof WebApp && $app->id == $this->backendAppId) {
            $app->request->parsers['application/json'] = 'yii\web\JsonParser';
            $this->controllerNamespace = __NAMESPACE__ . '\controllers\backend';
        }
        if ($app instanceof ConsoleApp) {
            array_push(
                $app->controllerMap['migrate']['migrationNamespaces'],
                __NAMESPACE__ . '\migrations'
            );
        }
        if (empty($this->uploadFilePath)) {
            $this->uploadFilePath = DIRECTORY_SEPARATOR .
                'web'. DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files';
        }
        if (empty($this->imageThumbPath)) {
            $this->imageThumbPath = DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'image-cache';
        }
        if (empty($this->backendAppId)) {
            throw new \InvalidArgumentException('Property backendAppId not set.');
        }
        if (empty($this->frontendDomainName)) {
            throw new \InvalidArgumentException('Frontend domain name not set.');
        }
    }

    private function _registerTranslation(BaseApp $app)
    {
        $app->i18n->translations[self::TRANSLATE] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en',
            'basePath'       => '@DmitriiKoziuk/yii2FileManager/messages',
        ];
    }

    /**
     * @param BaseApp $app
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    private function _registerClassesToDIContainer(BaseApp $app): void
    {
        $this->diContainer->setSingleton(FileHelper::class, function () {
            return new FileHelper(new BaseYii(), $this->uploadFilePath, $this->imageThumbPath);
        });
        $this->diContainer->setSingleton(FileRepository::class, function () {
            return new FileRepository();
        });
        /** @var FileHelper $fileHelper */
        $fileHelper = $this->diContainer->get(FileHelper::class);
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->diContainer->get(FileRepository::class);
        $this->diContainer->setSingleton(
            FileActionService::class,
            function () use ($fileRepository, $app, $fileHelper) {
                return new FileActionService(
                    new BaseYii(),
                    $this->uploadFilePath,
                    $fileHelper,
                    new UploadedFile(),
                    $fileRepository,
                    $app->db
                );
            }
        );
        /** @var FileActionService $fileActionService */
        $fileActionService = $this->diContainer->get(FileActionService::class);
        $this->diContainer->setSingleton(
            ThumbnailService::class,
            function () use ($fileRepository, $fileHelper, $fileActionService) {
                return new ThumbnailService($fileActionService, $fileRepository, $fileHelper);
            }
        );
        $this->diContainer->setSingleton(FileWebHelper::class, function () {
           return new FileWebHelper(
               $this->frontendDomainName,
               $this->uploadFilePath
           );
        });
    }
}
