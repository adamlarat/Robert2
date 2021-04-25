<?php
declare(strict_types=1);

namespace Robert2\API\Services;

use Slim\Views\Twig;
use Twig\TwigFunction;
use Robert2\API\I18n\I18n;
use Robert2\API\Config\Config;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Psr\Http\Message\ResponseInterface as Response;

final class View
{
    /** @var Twig */
    private $view;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $cachePath = false;
        if (Config::getEnv() === 'production') {
            $cachePath = VAR_FOLDER . DS . 'cache' . DS . 'views';
        }
        $this->view = Twig::create(VIEWS_FOLDER, ['cache' => $cachePath]);

        //
        // - Global variables
        //

        $this->view->getEnvironment()->addGlobal('env', Config::getEnv());

        //
        // - Extensions
        //

        $this->view->addExtension(new IntlExtension());
        $this->view->addExtension(new StringExtension());

        //
        // - Functions
        //

        $i18n = new I18n();
        $translate = new TwigFunction('translate', [$i18n, 'translate']);
        $version = new TwigFunction('version', $this->getVersion());
        $clientAssetFunction = new TwigFunction('client_asset', $this->getClientAsset());

        $this->view->getEnvironment()->addFunction($translate);
        $this->view->getEnvironment()->addFunction($version);
        $this->view->getEnvironment()->addFunction($clientAssetFunction);
    }

    // ------------------------------------------------------
    // -
    // -    Public methods
    // -
    // ------------------------------------------------------

    public function render(Response $response, string $template, array $data = []): Response
    {
        $response->getBody()->write($this->view->fetch($template, $data));
        return $response;
    }

    // ——————————————————————————————————————————————————————
    // —
    // —    Custom twig functions methods
    // —
    // ——————————————————————————————————————————————————————

    private function getVersion(): callable
    {
        return function (): string {
            return Config::getVersion();
        };
    }

    private function getClientAsset(): callable
    {
        $host = Config::getEnv() === 'development'
            ? 'http://localhost:8081'
            : '';

        $basePath = sprintf('%s/webclient', rtrim($host, '/'));
        return function ($path) use ($basePath) {
            return vsprintf('%s/%s?v=%s', [
                $basePath,
                ltrim($path, '/'),
                Config::getVersion(),
            ]);
        };
    }
}
