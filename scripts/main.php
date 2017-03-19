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

require_once('qrc://scripts/Notifications.php');
require_once('qrc://scripts/Notice.php');

class PQStudioCenter extends QWidget {
    
    const Hidden    = 0x00;
    const Showed    = 0x01;

    private $server;

    private $sockets = [];

    private $semaphore;

    private $memory;

    private $desktop;

    private $tray;
    
    private $animator;
    
    private $menu;
    
    private $notifications;
    
    private $geo = [];
    
    private $maxWidth;
    
    private $state;
    
    private $isQuit = false;
    
    private $bg;
    
    private $HideBtn;
    
    private $RemoveBtn;

    public function __construct($parent = null) {
        parent::__construct($parent);
        if($this->isOneInstance('PQStudioCenter')) {
            $this->initComponents();
        } else {
            die();
        }
    
        QFontDatabase::addApplicationFont(":/fonts/Akrobat.ttf");
        QFontDatabase::addApplicationFont(":/fonts/Akrobatblack.ttf");
        $this->state = self::Hidden;
        
        $this->styleSheet = '
            QWidget {
                font-family: "Akrobat";
                font-size: 16px;
            }
            QPushButton {
                border: 1px solid #515151;
                background: #393939;
                color: #c4c4c4;
                border-radius: 4px;
            }
            QPushButton:hover {
                border: 1px solid #c4c4c4;
            }
        ';
    }
    
    private function initComponents() {
        /** Получаем QDesktopWidget */
        $this->desktop = QApplication::desktop();
        /** Задаем заголовок для окна */
        $this->setWindowTitle(APP_TITLE);
        /** Задаем цвет для заливки фона */
        $this->bg = new QColor(57, 57, 57, 200);
        /** Инициализируем анимацию геометрии окна */
        $this->animator = new QPropertyAnimation($this);
        $this->animator->setTargetObject($this);
        $this->animator->setDuration(400);
        $this->animator->setPropertyName('geometry');
        /** Задаем обработчки который будет выполнен после окончания анимации */
        $this->animator->onFinished = function($sender) {
            if($this->state === self::Hidden) {
                parent::hide();
                if($this->isQuit) qApp()->quit();
            }
        };

        /** Задаем флаг для типа окна */
        $this->setWindowFlags(Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint);
        $this->setAttribute(Qt::WA_TranslucentBackground, true);
        /** Убираем автоматическую заливку фона */
        $this->setAutoFillBackground(false);
    
        $this->HideBtn = new QPushButton($this);
        $this->HideBtn->iconSize = new QSize(18, 18);
        $this->HideBtn->setIcon(new QIcon(':/angle-double-right.svg'));
        $this->HideBtn->setCursor(new QCursor(Qt::PointingHandCursor));
        $this->HideBtn->text = tr('Hide');
        $this->HideBtn->onClicked = function($sender) {
            $this->onHide();
        };
        
        $this->RemoveBtn = new QPushButton($this);
        $this->RemoveBtn->iconSize = new QSize(18, 18);
        $this->RemoveBtn->setIcon(new QIcon(':/trash-o.svg'));
        $this->RemoveBtn->setCursor(new QCursor(Qt::PointingHandCursor));
        $this->RemoveBtn->text = tr('Remove All');
        $this->RemoveBtn->onClicked = function($sender) {};
        
        /** Инициализируем икноку в трее */
        $this->initTrayIcon();
        /** Инициализируем локальный сокет сервер */
        $this->initLocalSocket();
        /** Высчитываем геометрию окна */
        $this->calculateGeometry();
    }
    
    /** @override paintEvent */
    public function paintEvent($event) {
        $customPainter = new QPainter($this);
        $customPainter->fillRect($this->rect(), $this->bg);
    }

    private function initTrayIcon() {
        /** Инициализируем объект для отображения иконки в трее */
        $this->tray = new QSystemTrayIcon($this);
        /** Создаем икноку из SVG */
        $icon = new QIcon(':/PQStudioCenter.svg');
        /** Задаем иконку для трея */
        $this->tray->setIcon($icon);

        /** Создаем меню */
        $this->menu = new QMenu();
        /** Создаем пункты меню и задаем действия для них */
        $minimize = new QAction(tr('Hide'), $this->menu);
        $minimize->onTriggered = function($sender) {
            $this->onHide();
        };
        $restore = new QAction(tr('Show'), $this->menu);
        $restore->onTriggered = function($sender) {
            $this->onShow();
        };
        $quit = new QAction(tr('Quit'), $this->menu);
        $quit->setIcon(new QIcon(':/power-off.svg'));
        $quit->onTriggered = function($sender) {
            $this->onQuit();
        };
        
        /** Добавляем созданные пункты в меню */
        $this->menu->addAction($minimize);
        $this->menu->addAction($restore);
        $this->menu->addAction($quit);
        /** Задаем меню как контекстное меню для иконки в трее */
        $this->tray->setContextMenu($this->menu);
        /** Отображаем иконку в трее */
        $this->tray->show();
        
        /** Задаем обработчик для иконки в трее */
        $this->tray->onActivated = function($sender, $reason) {
            switch($reason) {
                case QSystemTrayIcon::Trigger:
                case QSystemTrayIcon::DoubleClick:
                    $this->trayActionExecute();
                    break;
            }
        };
    }

    private function initLocalSocket() {
        /** Инициализируем локальный сокет сервер */
        $this->server = new QLocalServer($this);
        /** Запускаем сервер с именем */
        $this->server->listen('PQStudio Center');
        /** Задаем обработчик для новых соединений */
        $this->server->onNewConnection = function() {
            /** Получаем соединение */
            $socket = $this->server->naxtPendingConnection();
            /** Задаем обработчик для чтения принимаемых данных от соединения */
            $socket->onReadyRead = function($sender) {
                /** Читаем получаемые данные */
                $this->slot_readData($sender);
            };
            /** Задаем обработчик закрытия соединения со стороны клиента */
            $socket->onDisconnected = function($sender) {
                $index = array_search($sender, $this->sockets);
                if($index !== false) unset($this->sockets[$index]);
            };
            /** Добавляем соедиение в хранилище */
            $this->sockets[] = $socket;
        };
    }

    public function slot_incomingConnection() {
        $socket = $this->server->nextPendingConnection();
        $socket->connect(SIGNAL('readyRead()'), $this, SLOT('slot_readData()'));

        $this->sockets[] = $socket;
    }

    private function slot_readData($socket) {
        
    }

    public function slot_trayIconActivated($sender, $reason) {
        
    }

    private function getResolution() : array {
        /** Получаем номер главного экрана */
        $screenNumber = $this->desktop->primaryScreen();
        /** Получаем рабочую область главного экрана */
        $area = $this->desktop->availableGeometry($screenNumber);
        /** Получаем область всего экрана */
        $screenArea = $this->desktop->screenGeometry($screenNumber);
        /** Записываем во временный массив чтобы не вызывать каждый раз методы объектов */
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
        return $areas;
    }

    private function calculateGeometry() {
        /** Получаем массив доступного пространства */
        $area = $this->getResolution();
        /** Задаем ширину окна для отображения для разных разрешений */
        if($area['W1'] > 1280) $this->maxWidth = 400;
        if($area['W1'] <= 1280) $this->maxWidth = 350;
        
        $height = 0;
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
            $height = $area['H1'];
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
            $height = $area['H2'];
        }
        
        $this->notifications = new Notifications($this, $this->maxWidth, $height - 50);
    }

    private function setHideGeometry($x, $y, $w, $h) {
        $this->geo['hide'] = new QRect($x, $y, $w, $h);
    }

    private function setShowGeometry($x, $y, $w, $h) {
        $this->geo['show'] = new QRect($x, $y, $w, $h);
        $this->HideBtn->setGeometry(new QRect(10, $h - 40, $w / 2 - 15, 30));
        $this->RemoveBtn->setGeometry(new QRect($w / 2 + 5, $h - 40, $w / 2 - 15, 30));
    }

    private function trayActionExecute() {
        $this->hideOrShow();
    }

    private function isOneInstance($key) : bool {
        $isRunning = true;
        if($key !== '') {
            $this->semaphore = new QSystemSemaphore($key.'Semaphore', 1);
            $this->semaphore->acquire();
            $memory = new QSharedMemory($key.'Memory');
            $memory->attach();
            unset($memory);
            $this->memory = new QSharedMemory($key.'Memory');
            if($this->memory->attach()) {
                $isRunning = false;
            } else {
                $this->memory->create(1);
            }
            $this->semaphore->release();
        }
        return $isRunning;
    }
    
    private function hideOrShow() {
        if($this->state === self::Hidden) {
            $this->onShow();
        } else if($this->state === self::Showed) {
            $this->onHide();
        }
    }

    private function onShow() {
        if($this->state === self::Hidden) {
            $this->state = self::Showed;
            parent::show();
            $this->animator->setStartValue($this->geo['hide']);
            $this->animator->setEndValue($this->geo['show']);
            $this->animator->start();
        }
    }
    

    private function onHide() {
        if($this->state === self::Showed) {
            $this->state = self::Hidden;
            $this->animator->setStartValue($this->geo['show']);
            $this->animator->setEndValue($this->geo['hide']);
            $this->animator->start();
        }
    }

    private function onQuit() {
        $this->server->close();
        if($this->state === self::Hidden) {
            qApp()->quit();
        }
        if($this->state === self::Showed) {
            $this->isQuit = true;
            $this->onHide();
        }
    }
}

$window = new PQStudioCenter();

return $app->exec();
