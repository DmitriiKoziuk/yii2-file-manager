<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager;

use InvalidArgumentException;
use yii\di\Container;
use yii\di\NotInstantiableException;
use yii\web\Application as WebApp;
use yii\base\Application as BaseApp;
use yii\base\Module;
use yii\base\InvalidConfigException;
use yii\console\Application as ConsoleApp;
use yii\queue\cli\Queue;
use DmitriiKoziuk\yii2ModuleManager\interfaces\ModuleInterface;
use DmitriiKoziuk\yii2ModuleManager\ModuleManager;
use DmitriiKoziuk\yii2ConfigManager\ConfigManagerModule;

final class FileManagerModule extends Module implements ModuleInterface
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

    /**
     * @var string
     */
    public $imageThumbPath;

    /**
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function init()
    {
        /** @var BaseApp $app */
        $app = $this->module;
        $this->initLocalProperties($app);
        $this->registerTranslation($app);
        $this->registerClassesToDIContainer($app);
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
     * @throws InvalidArgumentException
     */
    private function initLocalProperties(BaseApp $app)
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
            throw new InvalidArgumentException('Property backendAppId not set.');
        }
        if (empty($this->frontendDomainName)) {
            throw new InvalidArgumentException('Frontend domain name not set.');
        }
    }

    private function registerTranslation(BaseApp $app)
    {
        $app->i18n->translations[self::TRANSLATE] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en',
            'basePath'       => '@DmitriiKoziuk/yii2FileManager/messages',
        ];
    }

    /**
     * @param BaseApp $app
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    private function registerClassesToDIContainer(BaseApp $app): void
    {

    }
}
