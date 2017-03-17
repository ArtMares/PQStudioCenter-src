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

//require_once('qrc://scripts/Object.php');

class PQStudioCenter extends QFrame {

    private $server;

    private $sockets = [];

    private $semaphore;

    private $memory;

    private $desktop;

    private $tray;

    private $animator;

    private $geo = [];

    private $maxWidth;

    private $isHide = false;

    private $isShow = false;
    
    private $isQuit = false;

    private $menu;

    public function __construct($parent = null) {
        parent::__construct($parent);

        if($this->isOneInstance('PQStudioCenter')) {
            $this->initComponents();
        } else {
            die();
        }
    }
    
    private function initComponents() {
        $this->desktop = QApplication::desktop();
        $this->setWindowTitle(APP_TITLE);

        $this->animator = new QPropertyAnimation($this);
        $this->animator->setTargetObject($this);
        $this->animator->setDuration(400);
        $this->animator->setPropertyName('geometry');

        $this->animator->onFinished = function($sender) {
            if($this->isHide) {
                parent::hide();
                if($this->isQuit) qApp()->quit();
            }
        };

        /** Задаем флаг для типа окна */
        $this->setWindowFlags(Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint);
//        $this->setWindowOpacity(0.5);
        $this->setAttribute(Qt::WA_TranslucentBackground);
//        $this->setWindowFlags(Qt::ToolTip);
//        /** Убираем автоматическую заливку фона QFrame */
        
        $this->setAutoFillBackground(true);
        
//        $this->styleSheet = 'background: #393939;';

        $this->initTrayIcon();

        $this->initLocalSocket();

        $this->calculateGeometry();
        
        $label = new QLabel($this);
        $label->text = 'TEste';
        $opacity = new QGraphicsOpacityEffect($label);
        $opacity->setOpacity(1);
        $label->setGraphicsEffect($opacity);
    }

    private function initTrayIcon() {
        $this->tray = new QSystemTrayIcon($this);
        $icon = new QIcon(':/PQStudioCenter.svg');
        $this->tray->setIcon($icon);

        $this->menu = new QMenu();
        $minimize = new QAction(tr('Roll up'), $this->menu);
        $minimize->connect(SIGNAL('triggered()'), $this, SLOT('onHide()'));
        $restore = new QAction(tr('Restore'), $this->menu);
        $restore->connect(SIGNAL('triggered()'), $this, SLOT('onShow()'));
        $quit = new QAction(tr('Quit'), $this->menu);
        $quit->connect(SIGNAL('triggered()'), $this, SLOT('onQuit()'));
        $this->menu->addAction($minimize);
        $this->menu->addAction($restore);
        $this->menu->addAction($quit);
        $this->tray->setContextMenu($this->menu);
        $this->tray->show();
        
        $this->tray->onActivated = function($sender, $reason) {
            switch($reason) {
                case QSystemTrayIcon::Trigger:
                case QSystemTrayIcon::DoubleClick:
                    $this->trayActionExecute();
                    break;
            }
        };

//        $this->tray->connect(SIGNAL('activated(int)'), $this, SLOT('slot_trayIconActivated(int)'));
    }

    private function initLocalSocket() {
        /** Инициализируем локальный сокет сервер */
        $this->server = new QLocalServer($this);
        /** Запускаем сервер с именем */
        $this->server->listen('PQStudio Center');
        /**  */
        $this->server->connect(SIGNAL('newConnection()'), $this, SLOT('incomingConnection()'));
    }

    public function slot_incomingConnection() {
        $socket = $this->server->nextPendingConnecttion();
        $socket->connect(SIGNAL('readyRead()'), $this, SLOT('slot_readData()'));

        $this->sockets[] = $socket;
    }

    public function slot_readData($socket) {

    }

    public function slot_trayIconActivated($sender, $reason) {
        
    }

    private function getResolution() {
        $screenNumber = $this->desktop->primaryScreen();
        $area = $this->desktop->availableGeometry($screenNumber);
        $screenArea = $this->desktop->screenGeometry($screenNumber);
        $areas = [
            'W1' => $screenArea->width(),
            'H1' => $screenArea->height(),
            'X1' => $screenArea->x(),
            'Y1' => $screenArea->y(),
            'W2' => $area->width(),
            'H2' => $area->height(),
            'X2' => $area->x(),
            'Y2' => $area->y(),
        ];
        qDebug($areas);
        return $areas;
    }

    private function calculateGeometry() {
        $area = $this->getResolution();
        if($area['W1'] > 1280) $this->maxWidth = 500;
        if($area['W1'] <= 1280) $this->maxWidth = 400;
        /** Проверяем нахождение Панели Задач Windows */
        /**
         * Если достпуная ширина меньше ширины экрана
         * То значит что Панель Задач находится либо слева, либо справа
         */
        if($area['W2'] < $area['W1']) {
            /** Ищем Панель Задач слева */
            if($area['X2'] > $area['X1']) {
                $this->setHideGeometry($area['W1'], $area['Y1'], 0, $area['H1']);
                $this->setShowGeometry($area['W1'] - $this->maxWidth, $area['Y1'], $this->maxWidth, $area['H1']);
            }
            /** Ищем Панель Задач справа */
            if($area['X2'] === $area['X1']) {
                $this->setHideGeometry($area['W2'], $area['Y1'], 0, $area['H1']);
                $this->setShowGeometry($area['W2'] - $this->maxWidth, $area['Y1'], $this->maxWidth, $area['H1']);
            }
        }
        /**
         * Если доступная высота меньше высоты экрана
         * То значит что Панель Задач находится либо сверху, либо снизу
         */
        if($area['H2'] < $area['H1']) {
            /** Ищем Панель Задач сверху */
            if($area['Y2'] > $area['Y1']) {
                $this->setHideGeometry($area['W1'], $area['Y2'], 0, $area['H2']);
                $this->setShowGeometry($area['W1'] - $this->maxWidth, $area['Y2'], $this->maxWidth, $area['H2']);
            }
            /** Ишем Панель Задач снизу */
            if($area['Y2'] === $area['Y1']) {
                $this->setHideGeometry($area['W1'], $area['Y1'], 0, $area['H2']);
                $this->setShowGeometry($area['W1'] - $this->maxWidth, $area['Y1'], $this->maxWidth, $area['H2']);
            }
        }

    }

    private function setHideGeometry($x, $y, $w, $h) {
        $this->geo['hide'] = new QRect($x, $y, $w, $h);
    }

    private function setShowGeometry($x, $y, $w, $h) {
        $this->geo['show'] = new QRect($x, $y, $w, $h);
    }

    private function trayActionExecute() {
        $this->onShow();
    }

    private function isOneInstance($key) {
        $isRunning = true;
        if($key !== '') {
            $this->semaphore = new \QSystemSemaphore($key.'Semaphore', 1);
            $this->semaphore->acquire();
            $memory = new \QSharedMemory($key.'Memory');
            $memory->attach();
            unset($memory);
            $this->memory = new \QSharedMemory($key.'Memory');
            if($this->memory->attach()) {
                $isRunning = false;
            } else {
                $this->memory->create(1);
            }
            $this->semaphore->release();
        }
        return $isRunning;
    }

    public function onShow() {
        if(!$this->isShow) {
            $this->isHide = false;
            $this->isShow = true;
            parent::show();
            $this->animator->setStartValue($this->geo['hide']);
            $this->animator->setEndValue($this->geo['show']);
            $this->animator->start();
        }
    }

    public function onHide() {
        if(!$this->isHide) {
            $this->isShow = false;
            $this->isHide = true;
            $this->animator->setStartValue($this->geo['show']);
            $this->animator->setEndValue($this->geo['hide']);
            $this->animator->start();
        }
    }

    public function onQuit() {
        $this->server->close();
        if($this->isHide) {
            qApp()->quit();
        }
        if($this->isShow) {
            $this->isQuit = true;
            $this->onHide();
        }
    }
}

$window = new PQStudioCenter();

return $app->exec();
