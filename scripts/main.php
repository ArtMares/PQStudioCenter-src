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

require_once('qrc://scripts/UID.php');
require_once('qrc://scripts/Notifications.php');
require_once('qrc://scripts/Notice.php');
require_once('qrc://scripts/Json.php');

class PQCenter extends QWidget {
    
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
    
    private $Btns;
    
    private $timer;
    
    private $i = 1;
    
    private $texts = [
        [
            'title' => 'Среда прозрачна, радиоизлучение которого достаточно сильное низкой',
            'message' => 'Все действующие точечные радиоисточники слились бы при сопоставлении галактик не об­наруживают. Попыток отождествления оптических и на две группы могли бы быть. Регистрировалось радиоизлучение, как только принадлежащие. Правило, в состав галактики, так. Радиоизлучение, как звезды, расстояния которых регистрировалось радиоизлучение, как мы указывали выше. Вина доля излучения, посылаемого в астрономии создалось несколько. Радиоволнах, больше, чем у звезд в том, что большинство.',
        ],
        [
            'title' => 'Небе близко друг к галактическому экватору это были известны',
            'message' => 'Том, что положение источника радиоизлучения второй группы могли бы при сопоставлении галактик. Обнаруживалось странное обстоятельство из источников, располагающихся вне этой полосы. Содержащей десятки квадратных минут нужно искать. Ожидает участь неотождествимости попыток отождествления галактических дискретных. Звезды, имеющие низкие температуры, 500к излучения, посылаемого в состав. Вызывалась тем, что положение источника радиоизлучения в источников. Звездной величины, никак не об­наруживают галактической концентрации этих галактик явля­лась источником.',
        ],
        [
            'title' => 'Наблюдались интенсивный источники радиоизлучения ожидает участь неотождествимости делятся',
            'message' => 'Слабых галактик очень много, и в перспективе можно надеяться на. Дискретными источниками радиоизлучения оптический объект нужно искать. 1950 г явилось лишь солнце радиоизлучение. Можно зарегистрировать, а слабых объектов кроме немногочисленных.',
        ],
        [
            'title' => 'Первые годы после открытия дискретных источников',
            'message' => 'Источника радиоизлучения определяется с источниками радиоизлучения оптический объект нужно искать. Труппы, как только галактики тоже будет все-таки слишком слабым, останется неуловимым распределенных. Все-таки слишком слабым, останется неуловимым больших расстояниях от нас останется неуловимым больших.',
        ],
        [
            'title' => 'Прозрачна, радиоизлучение можно зарегистрировать, а доля оптического излучения меньше толщины галактики',
            'message' => 'Нужно искать в целой площадке, содержащей десятки квадратных минут излучение. Доля излучения, посылаемого в том, что дискретными источниками радиоизлучения. Расстояний и в перспективе можно зарегистрировать, а оптическое излучение будет все-таки слишком.',
        ]
    ];
    
    public function __construct($parent = null) {
        parent::__construct($parent);
        if($this->isOneInstance('PQCenter')) {
            $this->initComponents();
        } else {
            die();
        }
        
        $this->objectName = 'PQCenter';
    
        QFontDatabase::addApplicationFont(":/fonts/Akrobat.ttf");
        QFontDatabase::addApplicationFont(":/fonts/Akrobatblack.ttf");
        $this->state = self::Hidden;
        
        $this->styleSheet = '
            QWidget#PQCenter {
                font-family: "Akrobat";
                font-size: 16px;
            }
            QFrame#Buttons QPushButton {
                font-family: "Akrobat";
                font-weight: 900;
                font-size: 16px;
                border: 1px solid #515151;
                background: #393939;
                color: #c4c4c4;
                border-radius: 4px;
            }
            QFrame#Buttons QPushButton:hover {
                border: 1px solid #c4c4c4;
            }
        ';
    }
    
    private function initComponents() {
        /** Получаем QDesktopWidget */
        $this->desktop = QApplication::desktop();
        /** Отлавливаем изменение рабочей области и изменияем геометрию в соответствии */
        $this->desktop->onWorkAreaResized = function($sender) {
            $this->calculateGeometry();
            if($this->state === self::Showed) $this->setGeometry($this->geo['show']);
        };
        /** Задаем заголовок для окна */
        $this->setWindowTitle(APP_TITLE);
        /** Задаем цвет для заливки фона */
        $this->bg = new QColor(57, 57, 57, 200);
        /** Инициализируем анимацию геометрии окна */
        $this->animator = new QPropertyAnimation($this);
        $this->animator->setTargetObject($this);
        $this->animator->setDuration(300);
        $this->animator->setPropertyName('geometry');
        /** Задаем обработчки который будет выполнен после окончания анимации */
        $this->animator->onFinished = function($sender) {
            if($this->state === self::Hidden) {
                parent::hide();
                if($this->isQuit) $this->onQuit();
            }
        };
        
        $this->timer = new QTimer();
        $this->timer->interval = 200;
        $this->timer->onTimeout = function($sender) {
            $this->traySetIcon($this->i);
            $this->i++;
        };

        /** Задаем флаг для типа окна */
        $this->setWindowFlags(Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint);
        $this->setAttribute(Qt::WA_TranslucentBackground, true);
        /** Убираем автоматическую заливку фона */
        $this->setAutoFillBackground(false);
        
        $this->Btns = new QFrame($this);
        $this->Btns->objectName = 'Buttons';
        $this->Btns->setLayout(new QHBoxLayout());
    
        $HideBtn = new QPushButton($this);
        $HideBtn->iconSize = new QSize(18, 18);
        $HideBtn->setMinimumHeight(30);
        $HideBtn->setIcon(new QIcon(':/light-hide.svg'));
        $HideBtn->setCursor(new QCursor(Qt::PointingHandCursor));
        $HideBtn->text = tr('Hide');
        $HideBtn->onClicked = function($sender) {
            $this->onHide();
        };
        
        $RemoveBtn = new QPushButton($this);
        $RemoveBtn->iconSize = new QSize(18, 18);
        $RemoveBtn->setMinimumHeight(30);
        $RemoveBtn->setIcon(new QIcon(':/light-trash.svg'));
        $RemoveBtn->setCursor(new QCursor(Qt::PointingHandCursor));
        $RemoveBtn->text = tr('Remove All');
        $RemoveBtn->onClicked = function($sender) {
            $this->notifications->removeAll();
        };
        
        $this->Btns->layout()->addWidget($HideBtn);
        $this->Btns->layout()->addWidget($RemoveBtn);
        
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
        
        $this->traySetIcon($this->i);
        
        $this->tray->toolTip = 'PQCenter';

        /** Создаем меню */
        $this->menu = new QMenu();
        /** Создаем пункты меню и задаем действия для них */
        $minimize = new QAction(tr('Hide'), $this->menu);
        $minimize->setIcon(new QIcon(':/hide.svg'));
        $minimize->onTriggered = function($sender) {
            $this->onHide();
        };
        $add = new QAction(tr('Add'), $this->menu);
        $add->onTriggered = function($sender) {
            $text = $this->texts[rand(0, 4)];
            $this->addNotice($text['title'], $text['message'], rand(2, 6));
        };
        $restore = new QAction(tr('Show'), $this->menu);
        $restore->setIcon(new QIcon(':/show.svg'));
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
        $this->menu->addAction($add);
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
    
    private function traySetIcon($i) {
        $this->tray->setIcon(new QIcon(':/logo/logo-step-'.($i % 2 === 0 ? 2 : 1).'.svg'));
    }

    private function initLocalSocket() {
        /** Инициализируем локальный сокет сервер */
        $this->server = new QLocalServer($this);
        /** Запускаем сервер с именем */
        $this->server->listen('PQCenter');
        /** Задаем обработчик для новых соединений */
        $this->server->connect(SIGNAL('newConnection()'), $this, SLOT('slot_incomingConnection()'));
    }
    
    public function slot_incomingConnection() {
        $socket = $this->server->nextPendingConnection();
        $socket->connect(SIGNAL('readyRead()'), $this, SLOT('slot_readData()'));
        $socket->connect(SIGNAL('disconnected()'), $this, SLOT('slot_onDisconnect()'));
        $this->sockets[] = $socket;
    }

    public function slot_readData($socket) {
        $data = $socket->readAll();
        $this->parseMsg($data);
    }
    
    public function slot_onDisconnect($socket) {
        $index = array_search($socket, $this->sockets);
        if($index !== false) unset($this->sockets[$index]);
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
                $this->setHideGeometry($area['X2'], $area['Y1'], 0, $area['H1']);
                $this->setShowGeometry($area['X2'], $area['Y1'], $this->maxWidth, $area['H1']);
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
        if(is_null($this->notifications)) {
            $this->notifications = new Notifications($this, $this->maxWidth, $height - 50);
        } else {
            $this->notifications->resize(new QSize($this->maxWidth, $height - 50));
        }
    }

    private function setHideGeometry($x, $y, $w, $h) {
        $this->geo['hide'] = new QRect($x, $y, $w, $h);
    }

    private function setShowGeometry($x, $y, $w, $h) {
        $this->geo['show'] = new QRect($x, $y, $w, $h);
        $this->Btns->setGeometry(new QRect(0, $h - 50, $w, 50));
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
            $this->timer->stop();
            $this->i = 1;
            $this->traySetIcon($this->i);
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
        foreach($this->sockets as $socket) $socket->close();
        if($this->state === self::Hidden) {
            qApp()->quit();
        }
        if($this->state === self::Showed) {
            $this->isQuit = true;
            $this->onHide();
        }
    }
    
    private function parseMsg($str) {
        $data = (new Json())->read($str);
        if(!is_null($data)) {
            $this->determineType($data);
        }
    }
    
    private function determineType($data) {
        if(isset($data['type'])) {
            switch($data['type']) {
                case 'notice':
                    $this->Notice($data);
                    break;
            }
        }
    }
    
    private function Notice($data) {
        if(isset($data['title']) && isset($data['message']) && isset($data['level'])) {
            if ($this->state === self::Hidden) $this->timer->start();
            $this->notifications->notice($data['title'], $data['message'], $data['level']);
        }
    }
}

$window = new PQCenter();

return $app->exec();
