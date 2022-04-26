<?php

namespace App;

use Exception;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

use const PHP_VERSION_ID;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @return iterable
     */
    public function registerBundles(): iterable
    {
        $strContents = include $this->getProjectDir() . '/config/bundles.php';
        foreach ($strContents as $strClass => $arEnvs) {
            if ($arEnvs[$this->environment] ?? $arEnvs['all'] ?? false) {
                yield new $strClass();
            }
        }
    }

    /**
     * @return string
     */
    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     * @return void
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir() . '/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
        $strConfDir = $this->getProjectDir() . '/config';

        $loader->load($strConfDir . '/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($strConfDir . '/{packages}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($strConfDir . '/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($strConfDir . '/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
    }

    /**
     * @param RoutingConfigurator $routes
     * @return void
     */
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/' . $this->environment . '/*.yaml');
        $routes->import('../config/{routes}/*.yaml');

        if (is_file(dirname(__DIR__) . '/config/routes.yaml')) {
            $routes->import('../config/routes.yaml');
        }
    }
}
