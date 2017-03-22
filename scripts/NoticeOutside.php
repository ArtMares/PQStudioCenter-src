<?php

/**
 * @author              Dmitriy Dergachev (ArtMares)
 * @date                20.03.2017
 * @copyright           artmares@influ.su
 */
class NoticeOutside extends Notice {
    
    public function __construct($title, $message, $level) {
        parent::__construct(null, $title, $message, $level);
    }
}