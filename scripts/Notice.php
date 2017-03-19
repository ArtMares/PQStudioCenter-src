<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                17.03.2017
 * @copyright           artmares@influ.su
 */
class Notice extends QWidget {
    
    const Unread    = 0x00;
    const Read      = 0x01;
    
    static public $w;
    
    private $closeBtn;
    
    private $parentWidget;
    
    public function __construct($parent = null, $title, $message) {
        parent::__construct($parent);
        
        $this->parentWidget = $parent;
        
        $this->styleSheet = '
            QWidget {
                border: 1px solid #c4c4c4;
            }
            QLabel {
                font-size: 16px;
                color: #c4c4c4;
            }
            QPushButton {
                width: 16px;
                height: 16px;
                border-radius: 8px;
                border: none;
                background: #ffffff;
            }
        ';

        $this->closeBtn = new QPushButton($this);
        
        $icon = new QIcon(':/close.svg');
        
        $this->closeBtn->iconSize = new QSize(16, 16);
        $this->closeBtn->setIcon($icon);
        $this->closeBtn->setCursor(new QCursor(Qt::PointingHandCursor));
        
        $this->setLayout(new QGridLayout());
        
        $labelTitle = new QLabel($this);
        $labelTitle->text = $title;
        $labelTitle->objectName = 'Title';
        $labelTitle->fontFamily = QFontDatabase::applicationFontFamilies($Akrobatblack)[0];
    
//        $this->layout()->setContentsMargins(0, 0, 0, 0);
//        $this->layout()->setSpacing(0);
        $this->layout()->addWidget($labelTitle, 0, 1);
        $this->layout()->addWidget($this->closeBtn, 0, 2);
        $this->layout()->setAlignment($this->closeBtn, Qt::AlignRight);
    }
    
    /** @override showEvent */
    public function showEvent($event) {
        $size = $this->size();
        $size = new QSize(self::$w - 20, $size->height());
        $this->resize($size);
    }
}