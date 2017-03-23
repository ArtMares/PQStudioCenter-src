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
    
    private $eventNotice;
    
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
        
        $eventFilter = new PQEventFilter($this);
        $eventFilter->addEventType(QEvent::Show);
        $this->installEventFilter($eventFilter);
        
        $eventFilter->onEvent = function($sender, $event) use($width, $height) {
            switch($event->type()) {
                case QEvent::Show:
                    if($this->firstShow) {
                        $this->resize(new QSize($width, $height));
                        $this->firstShow = !$this->firstShow;
                    }
                    break;
            }
        };
        
        $this->eventNotice = new PQEventFilter($this);
        $this->eventNotice->addEventType(QEvent::Close);
        $this->eventNotice->onEvent = function($sender, $event) {
            switch($event->type()) {
                case QEvent::Close:
                    $this->removeFromStack(array_search($sender, $this->stack));
                    break;
            }
        };
    }
    
    public function notice($title, $message, $level) {
        $notice = new Notice($this->list, $title, $message, $level);
        $notice->installEventFilter($this->eventNotice);
        $uid = UID::new();
        $this->stack[$uid] = $notice;
        $notice->setVisible(true);
        $this->list->layout()->addWidget($notice);
        qApp()->processEvents();
        $this->verticalScrollBar()->setValue($this->verticalScrollBar()->maximum());
    }
    
    public function event() {
        
    }
    
    public function removeAll() {
        foreach($this->stack as $notice) $notice->close();
    }
    
    private function removeFromStack($uid = false) {
        if($uid !== false) unset($this->stack[$uid]);
    }
}