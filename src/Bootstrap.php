<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager;

use Yii;
use yii\queue\file\Queue;
use yii\queue\LogBehavior;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use DmitriiKoziuk\yii2ConfigManager\ConfigManagerModule;
use DmitriiKoziuk\yii2ConfigManager\services\ConfigService;
use DmitriiKoziuk\yii2ModuleManager\services\ModuleRegistrationService;

final class Bootstrap implements BootstrapInterface
{
    /**
     * @param Application $app
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function bootstrap($app)
    {
        $app->bootstrap[] = 'dkFileManagerQueue';
        $queue = $app->get('dkFileManagerQueue');
        $container = Yii::$container;
        ModuleRegistrationService::addModule(
            FileManagerModule::class,
            function () use ($container, $queue) {
                /** @var ConfigService $configService */
                $configService = $container->get(ConfigService::class);
                return [
                    'class' => FileManagerModule::class,
                    'diContainer' => $container,
                    'queue' => $queue,
                    'backendAppId' => $configService->getValue(
                        ConfigManagerModule::GENERAL_CONFIG_NAME,
                        'backendAppId'
                    ),
                    'frontendDomainName' => $configService->getValue(
                        ConfigManagerModule::GENERAL_CONFIG_NAME,
                        'frontendDomainName'
                    ),
                ];
            }
        );
    }
}
