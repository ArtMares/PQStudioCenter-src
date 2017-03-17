<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                17.03.2017
 * @copyright           artmares@influ.su
 */
class Object extends QObject {

    public function __construct() {
        parent::__construct();

        $this->startTimer(400);
    }

    /** @override timerEvent */
    public function timerEvent($event) {
        qDebug('timeout');
    }
}