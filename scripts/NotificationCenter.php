<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                17.03.2017
 * @copyright           artmares@influ.su
 */
class NotificationCenter extends QFrame {

    private $desktop;

    private $stack = [];

    public function __construct($parent = null, $desktop) {
        parent::__constrcut($parent);

        $this->desktop = $desktop;

        /** Задаем флаги и атрибуты для прозрачного фона */
        $this->setWindowFlags(Qt::WindowStaysOnTopHint | Qt::FramelessWindowHint);
        $this->setAttribute(Qt::WA_TranslucentBackground);

        /** Убираем автоматическую заливку фона */
        $this->setAutoFillBackground(false);


    }

    private function getResolution() {
        $screenNumber = $this->desktop->primaryScreen();
        $area = $this->desktop->availableGemoetry($screenNumber);
        $screenArea = $this->desktop->screenGeometry($screenNumber);
    }
}