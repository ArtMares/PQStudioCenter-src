<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                17.03.2017
 * @copyright           artmares@influ.su
 */

class Notifications extends QScrollArea {

    private $stack = [];
    
    private $firstShow = true;
    
    private $list;
    
    private $eventFilter;

    public function __construct($parent = null, int $width, int $height) {
        parent::__construct($parent);
        
        $this->list = new QWidget();
        $this->list->setLayout(new QVBoxLayout());
        $this->list->layout()->setContentsMargins(0, 0, 0, 0);
        $this->list->layout()->setSpacing(5);
        $this->list->setMinimumWidth($width - 12);
    
        $listArea = new QWidget();
        $listArea->setLayout(new QVBoxLayout());
        $listArea->layout()->setContentsMargins(0, 0, 0, 0);
        $listArea->layout()->setSpacing(0);
        $listArea->layout()->addWidget($this->list);
        
        $spacer = new QWidget();
        $spacer->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Expanding);
        $listArea->layout()->addWidget($spacer);
        
        
        $this->setWidget($listArea);
        $this->setWidgetResizable(true);
        $this->setHorizontalScrollBarPolicy(\Qt::ScrollBarAlwaysOff);
        
        
        $this->styleSheet = '
            QScrollArea {
                background: transparent;
                border: none;
            }
            QScrollBar:vertical {
                border: none;
                background: rgba(0, 0, 0, 0);
                width: 12px;
                margin: 0px;
            }
            QScrollBar::handle:vertical {
                background: #c4c4c4;
                min-height: 30px;
                border: none;
                border-radius: 6px;
            }
            QScrollBar::add-line:vertical {
                border: 0px;
                height: 0px;
                subcontrol-position: bottom;
                subcontrol-origin: margin;
            }
            QScrollBar::sub-line:vertical {
                border: 0px;
                height: 0px;
                subcontrol-position: top;
                subcontrol-origin: margin;
            }
            QScrollBar::up-arrow:vertical {
                width: 0px;
                height: 0px;
            }
            QScrollBar::down-arrow:vertical {
                width: 0px;
                height: 0px;
            }
            QScrollBar::add-page:vertical, QScrollBar::sub-page:vertical {
                background: none;
            }
            QWidget {
                background: transparent;
            }
        ';
        
        $this->eventFilter = new PQEventFilter($this);
        $this->eventFilter->addEventType(QEvent::Show);
        $this->eventFilter->addEventType(QEvent::Close);
        $this->installEventFilter($this->eventFilter);
        
        $this->eventFilter->onEvent = function($sender, $event) use($width, $height) {
            switch($event->type()) {
                case QEvent::Show:
                    if($this->firstShow) {
                        $this->resize(new QSize($width, $height));
                        $this->firstShow = !$this->firstShow;
                    }
                    break;
                case QEvent::Close:
                    $class = get_class($sender);
                    if($class === 'Notice') {
                        $uid = array_search($sender, $this->stack);
                        if($uid !== false) unset($this->stack[$uid]);
                    }
            }
        };
    }
    
    public function add($title, $message, $level) {
        $notice = new Notice($this->list, $title, $message, $level);
        $notice->installEventFilter($this->eventFilter);
        $uid = (new UID())->generate();
        $this->stack[$uid] = $notice;
        $notice->setVisible(true);
        $this->list->layout()->addWidget($notice);
        qApp()->processEvents();
        $this->verticalScrollBar()->setValue($this->verticalScrollBar()->maximum());
    }
    
    public function removeAll() {
        foreach($this->stack as $notice) $notice->close();
    }
}