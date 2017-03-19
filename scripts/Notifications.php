<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                17.03.2017
 * @copyright           artmares@influ.su
 */
class Notifications extends QScrollArea {

    private $stack = [];
    
    private $firstShow = true;
    
    private $frame;

    public function __construct($parent = null, int $width, int $height) {
        parent::__construct($parent);
        
        $this->frame = new QFrame($this);
        $this->frame->setLayout(new QVBoxLayout());
        $this->frame->resize($width - 12, $height + 10);
        
        $this->setWidget($this->frame);
    
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
                background: rgba(196, 196, 196, 100);
                min-height: 30px;
                border: none;
                border-top-left-radius: 6px;
                border-bottom-left-radius: 6px;
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
            QFrame {
                background: transparent;
            }
        ';
        
        $eventFilter = new PQEventFilter($this);
        $eventFilter->addEventType(QEvent::Show);
//        $eventFilter->addEventType(QEvent::FocusOut);
        $this->installEventFilter($eventFilter);
        
        $eventFilter->onEvent = function($sender, $event) use($width, $height) {
            switch($event->type()) {
                case QEvent::Show:
                    if($this->firstShow) {
                        $this->resize(new QSize($width, $height));
                        $this->firstShow = !$this->firstShow;
                    }
                    break;
                case QEvent::FocusOut:
                    $parent = $this->parentWidget();
                    if($parent->state === PQStudioCenter::Showed) $this->parentWidget()->hide();
                    break;
            }
        };
    }
    
    public function new() {
        
    }
}