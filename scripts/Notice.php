<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                17.03.2017
 * @copyright           artmares@influ.su
 */
class Notice extends QWidget {
    
    const Unread    = 0x00;
    const Read      = 0x01;
    
    const None      = 0x02;
    const Success   = 0x03;
    const Info      = 0x04;
    const Warning   = 0x05;
    const Error     = 0x06;
    
    protected $state;
    
    public function __construct($parent = null, string $title, string $message, int $level = 0x02) {
        parent::__construct($parent);
        
        $this->initComponents($title, $message, $level);
    }
    
    public function initComponents($title, $message, $level) {
        $this->state = self::Unread;
        $this->objectName = 'Notice';
    
        $this->styleSheet = '
            QWidget {
                font-family: "Akrobat";
                font-size: 16px;
                background: #393939;
            }
            QLabel {
                color: #c4c4c4;
            }
            QLabel#Title {
                font-size: 18px;
            }
            QPushButton#Close {
                width: 16px;
                height: 16px;
                border: none;
                background: transparent;
            }
            ';
    
        $closeBtn = new QPushButton($this);
        $closeBtn->objectName = 'Close';
        $closeBtn->iconSize = new QSize(18, 18);
        $closeBtn->setIcon(new QIcon(':/light-close.svg'));
        $closeBtn->setCursor(new QCursor(Qt::PointingHandCursor));
        $closeBtn->setMaximumWidth(20);
        $closeBtn->onClicked = function($sender) {
            $this->close();
        };
    
        $this->setLayout(new QGridLayout());
    
        $icon = new QLabel($this);
        $icon->setMaximumWidth(20);
        switch($level) {
            case self::Success:
                $pixmap = new QIcon(':/success.svg');
                $icon->setPixmap($pixmap->pixmap(20, 20));
                break;
            case self::Info:
                $pixmap = new QIcon(':/info.svg');
                $icon->setPixmap($pixmap->pixmap(20, 20));
                break;
            case self::Warning:
                $pixmap = new QIcon(':/warning.svg');
                $icon->setPixmap($pixmap->pixmap(20, 20));
                break;
            case self::Error:
                $pixmap = new QIcon(':/error.svg');
                $icon->setPixmap($pixmap->pixmap(20, 20));
                break;
            default:
                $pixmap = new QIcon(':/none.svg');
                $icon->setPixmap($pixmap->pixmap(20, 20));
        }
    
        $labelTitle = new QLabel($this);
        $labelTitle->text = $this->cut($title, 75);
        $labelTitle->wordWrap = true;
        $labelTitle->objectName = 'Title';
    
        $labelMsg = new QLabel($this);
        $labelMsg->wordWrap = true;
        $labelMsg->text = $this->cut($message, 250);
    
        $row = 0;
        $this->layout()->addWidget($icon, $row, 0);
        $this->layout()->setAlignment($icon, Qt::AlignTop);
        $this->layout()->addWidget($labelTitle, $row, 1);
        $this->layout()->addWidget($closeBtn, $row, 2);
        $this->layout()->setAlignment($closeBtn, Qt::AlignTop);
    
        $row++;
        $this->layout()->addWidget($labelMsg, $row, 0, 1, 3);
    }
    
    protected function cut(string $str, int $len) : string {
        $str = new QString($str);
        if($str->length() > $len) {
            $str = new QString($str->left($len));
            $str = new QString($str->left($str->lastIndexOf(' ')));
            $str->append('...');
        }
        return $str->toUtf8();
    }
}