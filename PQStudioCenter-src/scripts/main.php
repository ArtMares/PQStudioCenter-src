<?php

$app = new QApplication($argc, $argv);
$app->applicationName = 'PQStudio Center';
$app->applicationVersion = '0.1';
$app->organizationName = 'PHPQt5 Team';
$app->organizationDomain = 'phpqt.ru';

define('RELEASE_VERSION', 'testing');
define('BUILD_VERSION', (string)100);

$title = sprintf('%1$s %2$s [build: %3$s]',
    $app->applicationName,
    $app->applicationVersion,
    BUILD_VERSION);

define('APP_TITLE', $title);

class PQStudioCenter extends QWidget {

    private $localSocket;
    
    public function __construct($parent = null) {
        parent::__construct($parent);
        
        $this->initComponents();
    }
    
    private function initComponents() {
        $this->windowTitle = APP_TITLE;
    }
}

$window = new PQStudioCenter();
$window->show();

return $app->exec();
